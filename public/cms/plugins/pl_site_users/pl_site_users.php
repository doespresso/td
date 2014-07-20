<?php

/**
 * Функции управления пользователями сайта
 */

function fSite_Users_Get($args)
{
    $fio  = $args['su_name'];
    $mail = $args['su_email'];
    $foto = $args['su_pers_foto'];

    if ($foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$foto))
    {
        $ar = $GLOBALS['sbVfs']->getimagesize(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$foto);
        if (!$ar || !in_array($ar[2], array(1, 2, 3)))
        {
            $foto = '<img src="'.SB_CMS_IMG_URL.'/nofoto.png" width="88" height="88" alt="'.$args['su_id'].'" title="'.$args['su_id'].'" />';
        }
        else
        {
            $foto_width = min(100, $ar[0]);
            $foto = '<img src="'.SB_SITE_USER_UPLOAD_URL.'/pl_site_users/'.$foto.'" alt="'.$args['su_id'].'" title="'.$args['su_id'].'" width="'.$foto_width.'" />';
        }
    }
    else
    {
        $foto = '<img src="'.SB_CMS_IMG_URL.'/nofoto.png" width="88" height="88" alt="'.$args['su_id'].'" title="'.$args['su_id'].'" />';
    }

    $reg_date = $args['su_reg_date'];
    if ($reg_date)
        $reg_date = sb_date('d.m.Y '.KERNEL_IN.' H:i', $reg_date);
    else
        $reg_date = '-';

    $last_date = $args['su_last_date'];
    if ($last_date)
        $last_date = sb_date('d.m.Y '.KERNEL_IN.' H:i', $last_date);
    else
        $last_date = '-';

    $last_ip = $args['su_last_ip'] ? $args['su_last_ip'] : '-';

    $su_domains = array();
    if ($args['su_domains'] != '')
    {
        require_once (SB_CMS_LIB_PATH.'/sbIDN.inc.php');
        $idn = new sbIDN();

        $su_domains = explode(',', $args['su_domains']);
        foreach ($su_domains as $key => $value)
        {
            $domain = $value;
            if ($value == SB_COOKIE_DOMAIN && SB_PORT != '80')
                $domain .= ':'.SB_PORT;

            if ('http://'.$domain != SB_DOMAIN && substr_count($domain, '.') > 0)
                $domain = 'www.'.$domain;

            $domain = 'http://'.$domain;

            $su_domains[$key] = '<a href="'.$domain.'" target="_blank" style="color: #cc0066;">'.$idn->decode($value).'</a>';
        }
        $su_domains = implode(', ', $su_domains);
    }

    if($args['su_login'] == '') $args['su_login'] = $mail;

    $result = '<table width="100%" cellpadding="0" cellspacing="0">
    <tr><td width="110" align="center">'.$foto.'</td>
    <td valign="top"><b><a href="javascript:void(0);" onclick="sbElemsEdit(event);" id="su_title_'.$args['su_id'].'">'.$args['su_login'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">'
    .PL_SITE_USERS_EDIT_ID.': <span style="color: #33805E;">'.(isset($args['su_id']) ? $args['su_id'] : '').'</span><br />'
    .($fio != '' ? PL_SITE_USERS_EDIT_FIO.': <span style="color: #33805E;">'.$fio.'</span><br />' : '')
    .($mail != '' ? PL_SITE_USERS_EDIT_EMAIL.': <span style="color: #33805E;">'.$mail.'</span><br />' : '')
    .($args['su_work_name'] != '' ? PL_SITE_USERS_EDIT_WORK_NAME.': <span style="color: #33805E;">'.$args['su_work_name'].'</span><br />' : '')
    .PL_SITE_USERS_GET_REG_DATE.': <span style="color: #33805E;">'.$reg_date.'</span><br />'
    .PL_SITE_USERS_GET_LAST_DATE.': <span style="color: #33805E;">'.$last_date.' (IP: '.$last_ip.')</span><br />'
    .(count($su_domains) != 0 ? PL_SITE_USERS_EDIT_DOMAINS.': <span style="color: #33805E;">'.$su_domains.'</span><br />' : '')
    .PL_SITE_USERS_EDIT_STATUS.': ';

    switch ($args['su_status'])
    {
        case 0:
            $result .= '<span style="color: green;">'.PL_SITE_USERS_GET_STATUS_REG.'</span>';
            break;
        case 1:
            $result .= '<span style="color: magenta;">'.PL_SITE_USERS_GET_STATUS_MOD.'</span>';
            break;
        case 2:
            $result .= '<span style="color: magenta;">'.PL_SITE_USERS_GET_STATUS_EMAIL.'</span>';
            break;
        case 3:
            $result .= '<span style="color: magenta;">'.PL_SITE_USERS_GET_STATUS_MOD_EMAIL.'</span>';
            break;
        case 4:
            $result .= '<span style="color: red;">'.PL_SITE_USERS_GET_STATUS_BLOCK.'</span>';
            break;
    }

    $result .= fComments_Get_Count_Get($args['su_id'], 'pl_site_users');
    $result .= fVoting_Rating_Get($args['su_id'], 'pl_site_users');

    $result .= '</div> </td></tr></table>';

    return $result;
}

function fSite_Users_Create_System_Categs()
{
    // проверяем, есть ли группы пользователей
    $res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_ident="pl_site_users"');
    if (!$res || $res[0][0] == 0)
    {
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree('pl_site_users');

        $fields = array();
        $fields['cat_title'] = PL_SITE_USERS_ROOT_NAME;
        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = 1;
        $fields['cat_rights'] = $_SESSION['sbAuth']->isAdmin() ? '' : 'u'.$_SESSION['sbAuth']->getUserId();

        $cat_fields = array();
        $cat_fields['cat_user_lang'] = SB_CMS_LANG;
        $cat_fields['cat_status'] = 0;
        $cat_fields['cat_domains'] = array_keys($GLOBALS['sb_domains']);

        $fields['cat_fields'] = serialize($cat_fields);

        $tree->insertNode(0, $fields);
    }
}

function fSite_Users_Init(&$elems = '', $external = false)
{
    fSite_Users_Create_System_Categs();

    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users', 'su_id', 'su_login', 'fSite_Users_Get', 'pl_site_users_init', 'pl_site_users');
    $elems->mCategsRootName = PL_SITE_USERS_ROOT_NAME;
    $elems->mShowIcons = false;

    $elems->addField('su_email');
    $elems->addField('su_name');
    $elems->addField('su_pers_foto');
    $elems->addField('su_work_name');
    $elems->addField('su_domains');
    $elems->addField('su_reg_date');
    $elems->addField('su_last_date');
    $elems->addField('su_last_ip');
    $elems->addField('su_status');

    $elems->addFilter(PL_SITE_USERS_EDIT_ID,        'su_id', 'number');
    $elems->addFilter(PL_SITE_USERS_EDIT_LOGIN2,    'su_login', 'string');
    $elems->addFilter(PL_SITE_USERS_EDIT_FIO,       'su_name',  'string');
    $elems->addFilter(PL_SITE_USERS_GET_REG_DATE,   'su_reg_date', 'date');
    $elems->addFilter(PL_SITE_USERS_GET_LAST_DATE,  'su_last_date', 'date');
    $elems->addFilter(PL_SITE_USERS_EDIT_STATUS,    'su_status', 'select', array(0 => PL_SITE_USERS_GET_STATUS_REG,
                                                                              1 => PL_SITE_USERS_GET_STATUS_MOD,
                                                                              2 => PL_SITE_USERS_GET_STATUS_EMAIL,
                                                                              3 => PL_SITE_USERS_GET_STATUS_MOD_EMAIL,
                                                                              4 => PL_SITE_USERS_GET_STATUS_BLOCK));

    $elems->addFilter(PL_SITE_USERS_EDIT_EMAIL,     'su_email', 'string');
    $elems->addFilter(PL_SITE_USERS_EDIT_WORK_NAME,     'su_work_name', 'string');

    $elems->addSorting(PL_SITE_USERS_SORT_BY_ID,         'su_id');
    $elems->addSorting(PL_SITE_USERS_SORT_BY_LOGIN,      'su_login');
    $elems->addSorting(PL_SITE_USERS_SORT_BY_EMAIL,      'su_email');
    $elems->addSorting(PL_SITE_USERS_SORT_BY_NAME,       'su_name');
    $elems->addSorting(PL_SITE_USERS_SORT_BY_REG_DATE,   'su_reg_date');
    $elems->addSorting(PL_SITE_USERS_SORT_BY_LAST_DATE,  'su_last_date');

    $elems->mCategsAddMenuTitle = PL_SITE_USERS_EDIT_CAT_ADD;
    $elems->mCategsEditMenuTitle = PL_SITE_USERS_CATEGS_EDIT_MENU_TITLE;
    $elems->mCategsDeleteMenuTitle = PL_SITE_USERS_CATEGS_DELETE_MENU_TITLE;
    $elems->mCategsPasteMenuTitle = PL_SITE_USERS_CATEGS_PASTE_MENU_TITLE;
    $elems->mCategsCopyMenuTitle = PL_SITE_USERS_CATEGS_COPY_MENU_TITLE;
    $elems->mCategsCutMenuTitle = PL_SITE_USERS_CATEGS_CUT_MENU_TITLE;
    $elems->mCategsPasteLinksMenuTitle = PL_SITE_USERS_CATEGS_PASTE_LINKS_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = PL_SITE_USERS_CATEGS_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle = PL_SITE_USERS_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = PL_SITE_USERS_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = PL_SITE_USERS_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle = PL_SITE_USERS_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_edit';
    $elems->mElemsEditDlgWidth = 800;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_edit';
    $elems->mElemsAddDlgWidth = 800;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent =  'pl_site_users_delete';

    $elems->mCategsAddEvent = 'pl_site_users_cat_edit';
    $elems->mCategsAddDlgWidth = 800;
    $elems->mCategsAddDlgHeight = 730;

    $elems->mCategsEditEvent = 'pl_site_users_cat_edit';
    $elems->mCategsEditDlgWidth = 800;
    $elems->mCategsEditDlgHeight = 700;

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = true;
    $elems->mCategsPasteElemsMenu = 'cut';
    $elems->mElemsUseLinks = true;

    $elems->mElemsJavascriptStr = '
    var timer = "";
    function showComments(su_id)
    {
        if (typeof(su_id) == "undefined")
        {
            su_id = sbSelEl.getAttribute("el_id");
        }

        var su_title = sbGetE("su_title_" + su_id);

        if (su_title)
        {
            if(timer != "undefined" && timer != "")
                clearInterval(timer);

            sbShowCommentaryWindow("pl_site_users", su_id, su_title.innerHTML);
        }
    }';

    if(isset($_GET['sb_sel_id']) && $_GET['sb_sel_id'] != '' && isset($_GET['show_comments']))
    {
        $elems->mFooterStr = '
            <script>
                var timer = setInterval("showComments('.$_GET['sb_sel_id'].')", 300);
            </script>';
    }
    $elems->addElemsMenuItem(PL_SITE_USERS_EDIT_SHOW_COMMENT, 'showComments()', false);

    $elems->mElemsJavascriptStr .= '
        function sbSuExport(c)
        {
            if(c && sbSelCat)
            {
                var cat_id = sbSelCat.id;
            }
            else
            {
                var cat_id = sbCatTree.getSelectedItemId();
            }

            var args = new Object();
            var form = sbGetE("filter_form");
            if(form)
            {
                var els = form.elements;
                for(var i = 0; i < els.length; i++)
                {
                    if(els[i] && els[i].type != "button" && els[i].value != "")
                    {
                        var reg_date = new RegExp("date_");
                        var reg_spin = new RegExp("spin_");

                        if(reg_date.test(els[i].id))
                        {
                            args[els[i].name] = {};
                            args[els[i].name]["value"]= els[i].value;
                            args[els[i].name]["type"]= "date";
                        }
                        else if(reg_spin.test(els[i].id))
                        {
                            args[els[i].name] = {};
                            args[els[i].name]["value"]= els[i].value;
                            args[els[i].name]["type"]= "number";
                        }
                        else if(els[i].nodeName == "SELECT" && els[i].multiple)
                        {
                            var str = "";
                            for(var j = 0; j < els[i].length; j++)
                            {
                                if(els[i][j].selected)
                                    str += els[i][j].value+",";
                            }
                            str = str.slice(0, -1);

                            args[els[i].name] = {};
                            args[els[i].name]["value"]= str;
                            args[els[i].name]["type"]= "multyselect";
                        }
                        else if(els[i].nodeName == "SELECT")
                        {
                            var reg_link = new RegExp("_link");
                            if(els[i].value == -1 && reg_link.test(els[i].id))
                            {
                                var name = els[i].name.replace(/_link/, "");
                                args[name] = "";
                                continue;
                            }
                            else if(els[i].value == -1)
                            {
                                continue;
                            }

                            args[els[i].name] = {};
                            args[els[i].name]["value"]= els[i].value;
                            args[els[i].name]["type"]= "select";
                        }
                        else if(els[i].type == "ckeckbox")
                        {
                            args[els[i].name] = {};
                            args[els[i].name]["value"]= els[i].value;
                            args[els[i].name]["type"]= "checkbox";
                        }
                        else if(els[i].type == "text")
                        {
                            args[els[i].name] = {};
                            args[els[i].name]["value"]= els[i].value;
                            args[els[i].name]["type"]= "string";
                        }
                    }
                }
            }
            var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_edit&ident=pl_site_users&cat_id="+ cat_id;
            var strAttr = "resizable=1,width=700,height=700";
            sbShowModalDialog(strPage, strAttr, null, args);
        }
        function sbSuImport(c)
        {
            if(c && sbSelCat)
            {
                var cat_id = sbSelCat.id;
            }
            else
            {
                var cat_id = sbCatTree.getSelectedItemId();
            }

            var args = new Object();

            var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_export_import_edit&ident=pl_site_users&cat_id="+ cat_id;
            var strAttr = "resizable=1,width=650,height=370";
            sbShowModalDialog(strPage, strAttr, sbAfterSuImport, args);
        }

        function sbAfterSuImport()
        {
            window.location.href = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
        }';

    $elems->addElemsMenuItem(PL_SITE_USERS_EDIT_EXPORT, 'sbSuExport()', false);
    //$elems->addElemsMenuItem(PL_SITE_USERS_EDIT_IMPORT, 'sbSuImport()', false);
    $elems->addCategsMenuItem(PL_SITE_USERS_EDIT_EXPORT, 'sbSuExport(true)');
    //$elems->addCategsMenuItem(PL_SITE_USERS_EDIT_IMPORT, 'sbSuImport(true)');

    if(!$external)
        $elems->init();
}

function fSite_Users_Check_Login()
{
    $res = sql_param_query('SELECT su_id FROM sb_site_users WHERE su_login=? AND su_id != ?d', $_GET['login'], $_GET['id']);
    if ($res)
        echo 'TRUE';
    else
        echo 'FALSE';
}

function fSite_Users_Check_Email()
{
    $res = sql_param_query('SELECT su_id FROM sb_site_users WHERE su_email=? AND su_id != ?d', $_GET['email'], $_GET['id']);
    if ($res)
        echo 'TRUE';
    else
        echo 'FALSE';
}

function fSite_Users_Edit()
{
    $edit_group = sbIsGroupEdit();

    // проверка прав доступа
    if ($edit_group)
    {
        if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_site_users'))
            return;
    }
    elseif (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users'))
    {
        return;
    }

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
    {
        $result = sql_param_query('SELECT su_last_ip, su_name, su_active_date, su_email, su_login, su_reg_date, su_last_date, su_domains,
                                   su_status, su_pers_foto, su_pers_phone, su_pers_mob_phone, su_pers_birth,
                                   su_pers_sex, su_pers_zip, su_pers_adress, su_pers_addition, su_work_name,
                                   su_work_phone, su_work_phone_inner, su_work_fax, su_work_email, su_work_addition, su_work_office_number, su_work_unit,su_work_position,
                                   su_forum_nick, su_forum_text, su_mail_lang, su_mail_status, su_mail_date, su_mail_subscription FROM sb_site_users WHERE su_id=?d', $_GET['id']);

        if ($result)
        {
            list($su_last_ip, $su_name, $su_active_date, $su_email, $su_login, $su_reg_date, $su_last_date,
                 $su_domains, $su_status, $su_pers_foto, $su_pers_phone, $su_pers_mob_phone, $su_pers_birth, $su_pers_sex,
                 $su_pers_zip, $su_pers_adress, $su_pers_addition, $su_work_name, $su_work_phone, $su_work_phone_inner,
                 $su_work_fax, $su_work_email, $su_work_addition, $su_work_office_number,
                 $su_work_unit, $su_work_position, $su_forum_nick, $su_forum_text,$su_mail_lang,$su_mail_status,$su_mail_date,$su_mail_subscription) = $result[0];

                 $su_mail_subscription = explode(',', $su_mail_subscription);

                 if($su_active_date != 0)
                 {
                    $su_active_date = sb_date('d.m.Y', $su_active_date);
                 }
                 else
                 {
                    $su_active_date = '';
                 }

                 if($su_mail_date != 0)
                 {
                    $su_mail_date = sb_date('d.m.Y', $su_mail_date);
                 }
                 else
                 {
                    $su_mail_date = '';
                 }

                 if($su_pers_birth != 0)
                 {
                    $su_pers_birth = sb_date('d.m.Y', $su_pers_birth);
                 }
                 else
                 {
                    $su_pers_birth = '';
                 }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_ERROR, true, 'warning');
            return;
        }

        $su_pass = '';


        if ($su_pers_foto == '' || !$GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto))
        {
            $su_pers_foto = '';
        }
        else
        {
            $ar = $GLOBALS['sbVfs']->getimagesize(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto);
            if (!$ar || !in_array($ar[2], array(1,2,3)))
            {
                // не картинка
                $su_pers_foto = '';
            }
            else
            {
                $su_pers_foto = SB_SITE_USER_UPLOAD_URL.'/pl_site_users/'.$su_pers_foto;
            }
        }

        if (is_null($su_last_ip))
            $su_last_ip = '-';

        $su_domains = explode(',', $su_domains);
    }
    else
    {
        $su_name = $su_active_date = $su_pass = $su_email = $su_login = $su_pers_foto = $su_pers_phone = $su_pers_mob_phone = $su_pers_birth =
        $su_pers_sex = $su_pers_zip = $su_pers_adress = $su_pers_addition = $su_work_name =
        $su_work_phone = $su_work_phone_inner = $su_work_fax = $su_work_email = $su_work_addition = $su_work_office_number =
        $su_work_unit = $su_work_position = $su_forum_nick = $su_forum_text = $su_mail_date = '';

        $su_domains = array();
        $su_mail_status = 0;
        $su_mail_lang = SB_CMS_LANG;
        $su_mail_subscription = array();

        $res=sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id=?d AND cat_ident="pl_site_users"', $_GET['cat_id']);
        list($cat_fields) = $res[0];

        if($cat_fields != '')
        {
            $cat_fields = unserialize($cat_fields);
            $su_status = $cat_fields['cat_status'];
            $su_domains = isset($cat_fields['cat_domains']) && is_array($cat_fields['cat_domains']) ? $cat_fields['cat_domains'] : array();
        }
        else
        {
            $su_status = 0;
            $su_domains[] = SB_COOKIE_DOMAIN;
        }

        $su_reg_date = $su_last_date = $su_last_ip = null;

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }

    if (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_POST['su_domains']))
        {
            $su_domains = array();
        }
    }

    $pswrd_len = 0;

    echo '<script>
                    function checkValues()
                    {';
            if (!$edit_group)
            {
                    echo '
                        var su_login = sbGetE("su_login");
                        var su_pass_1 = sbGetE("su_pass1");
                        var su_pass_2 = sbGetE("su_pass2");
                        var su_email = sbGetE("su_email");

                        if (su_login.value == "" && su_email.value == "")
                        {
                           alert("'.PL_SITE_USERS_NO_LOGIN_AND_E_MAIL.'");
                           return false;
                        }

                        if (su_login.value != "" && su_login.value.length <'.sbPlugins::getSetting('pl_site_users_login_length').')
                        {
                            alert("'.PL_SITE_USERS_WRONG_LOGIN_MSG.'");
                            return false;
                        }

                        var myReg = /^\w+[\.\w\-_]*@\w+[\.\w\-]*\w\.\w{2,6}$/;
                        if(su_email.value != "" && !myReg.test(su_email.value))
                        {
                            alert("'.PL_SITE_USERS_NO_E_MAIL.'");
                            return false;
                        }';


        $pswrd_len = sbPlugins::getSetting('pl_site_users_password_length');
        if ($_GET['id'] == '' && $pswrd_len != 0)
        {
            echo '      if (su_pass_1.value.length < '.$pswrd_len.')
                        {
                            alert("'.PL_SITE_USERS_WRONG_PASS_MSG2.'");
                            return false;
                        }';
        }
        elseif($pswrd_len != 0)
        {
            echo '      if (su_pass_1.value != "" && su_pass_1.value.length <'.$pswrd_len.')
                        {
                            alert("'.PL_SITE_USERS_WRONG_PASS_MSG2.'");
                            return false;
                        }';
        }

        if($pswrd_len != 0)
        {
            echo '      if (su_pass_1.value != su_pass_2.value)
                        {
                            alert("'.PL_SITE_USERS_WRONG_PASS_MSG.'");
                            return false;
                        }';
        }

        if($pswrd_len != 0 || $_GET['id'] != '')
        {
            echo '      if (su_email.value != "")
                        {
                            var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_site_users_check_email&id='.$_GET['id'].'&email=" + su_email.value);
                            if (res == "TRUE")
                            {
                                alert("'.PL_SITE_USERS_SAME_EMAIL_MSG.'");
                                return false;
                            }
                        }';
        }
            echo '
                        if (su_login.value != "")
                        {
                            var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_site_users_check_login&id='.$_GET['id'].'&login=" + su_login.value);
                            if (res == "TRUE")
                            {
                                alert("'.PL_SITE_USERS_SAME_LOGIN_MSG.'");
                                return false;
                            }
                        }';
            }

            echo '
                        return true;
                    }
                    function checkMaillist(el)
                    {
                        var i = 1;
                        var tmp = sbGetE(el.id + "_" + i);
                        while(tmp)
                        {
                            tmp.checked = el.checked;
                            i++;
                            tmp = sbGetE(el.id + "_" + i);
                        }
                     }

                     function uncheckMaillist(el, par_id)
                     {
                        var i = 1;
                        var tmp = sbGetE(par_id + "_" + i);
                        var check = false;

                        while(tmp)
                        {
                            if(tmp.checked)
                            {
                                check = true;
                                break;
                            }

                            i++;
                            tmp = sbGetE(par_id + "_" + i);
                        }

                        sbGetE(par_id).checked = check;
                     }';

    if($edit_group)
    {
        echo '
            function cancel()
            {
                sbReturnValue("refresh");
            }
            sbAddEvent(window, "close", cancel);';
    }
    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_edit_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', '', 'enctype="multipart/form-data"');

    $layout->addTab(PL_SITE_USERS_TAB1);
    $layout->addHeader(PL_SITE_USERS_TAB1);

    // Вкладка "Основные"
    if (!$edit_group)
    {
        $layout->addField(PL_SITE_USERS_EDIT_LOGIN, new sbLayoutInput('text', $su_login, 'su_login', '', 'style="width:200px;" autocomplete="off"', true));
        $layout->addField(PL_SITE_USERS_EDIT_EMAIL, new sbLayoutInput('text', $su_email, 'su_email', '', 'style="width:200px;" autocomplete="off"', true));
        $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_SITE_USERS_LOGIN_EMAIL_HINT.'</div>', '', '', false));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutInput('password', $su_pass, 'su_pass1', '', 'style="width:200px;" autocomplete="off" onkeyup="sb_pass_strength(\'su_pass1\', \'su_login\', \'pass_meter\', '.intval(sbPlugins::getSetting('pl_site_users_password_length')).');"', ($pswrd_len != 0 ? true : false));
        $fld->mHTML = '<div id="pass_meter">'.PL_SITE_USERS_EDIT_PASS_METER.'</div>';
        $layout->addField(PL_SITE_USERS_EDIT_PASS1, $fld);
        $layout->addField(PL_SITE_USERS_EDIT_PASS2, new sbLayoutInput('password', '', 'su_pass2', '', 'style="width:200px;" autocomplete="off"', ($pswrd_len != 0 ? true : false)));
        $layout->addField('', new sbLayoutDelim());
    }

    $html  = '<div style="margin-top:5px;"><input type="radio" value="0" name="su_status" id="su_status0" style="margin:0px;"'.(($su_status==0) ? ' checked' : '').'>&nbsp;<label for="su_status0">'.PL_SITE_USERS_GET_STATUS_REG.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="1" name="su_status" id="su_status1" style="margin:0px;"'.(($su_status==1) ? ' checked' : '').'>&nbsp;<label for="su_status1">'.PL_SITE_USERS_GET_STATUS_MOD.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="2" name="su_status" id="su_status2" style="margin:0px;"'.(($su_status==2) ? ' checked' : '').'>&nbsp;<label for="su_status2">'.PL_SITE_USERS_GET_STATUS_EMAIL.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="3" name="su_status" id="su_status3" style="margin:0px;"'.(($su_status==3) ? ' checked' : '').'>&nbsp;<label for="su_status3">'.PL_SITE_USERS_GET_STATUS_MOD_EMAIL.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="4" name="su_status" id="su_status4" style="margin:0px;"'.(($su_status==4) ? ' checked' : '').'>&nbsp;<label for="su_status4">'.PL_SITE_USERS_GET_STATUS_BLOCK.'</label></div>';

    $hfld = new sbLayoutHTML($html);
    if ($edit_group)
    {
        $hfld->mShowColon = false;
    }

    $layout->addField(PL_SITE_USERS_EDIT_STATUS.sbGetGroupEditCheckbox('su_status', $edit_group), $hfld);

    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutDate($su_active_date, 'su_active_date');
    $field->mShowTime = false;
    $field->mDropButton = true;

    $layout->addField(PL_SITE_USERS_EDIT_ACTIVE_DATE.sbGetGroupEditCheckbox('su_active_date', $edit_group), $field);
    $layout->addField('', new sbLayoutDelim());

    require_once (SB_CMS_LIB_PATH.'/sbIDN.inc.php');
    $idn = new sbIDN();

    $html = '';
    foreach ($GLOBALS['sb_domains'] as $key => $value)
    {
        $html .= '<div style="margin-top:5px;"><input type="checkbox" value="'.$key.'" name="su_domains[]" id="su_domains'.$key.'" style="margin:0px;"'.(in_array($key, $su_domains) ? ' checked' : '').'>&nbsp;<label for="su_domains'.$key.'">'.$idn->decode($key).'</label></div>';
    }

    $hfld = new sbLayoutHTML($html);
    $hfld->mShowColon = false;

    $layout->addField(PL_SITE_USERS_EDIT_DOMAINS.sbGetGroupEditCheckbox('su_domains', $edit_group), $hfld);

    if ($_GET['id'] != '' && !$edit_group)
    {
        $layout->addField('', new sbLayoutDelim());
        $layout->addField(PL_SITE_USERS_GET_REG_DATE, new sbLayoutLabel($su_reg_date ? sb_date('d.m.Y '.KERNEL_IN.' H:i', $su_reg_date) : '-'));
        $layout->addField(PL_SITE_USERS_GET_LAST_DATE, new sbLayoutLabel($su_last_date ? sb_date('d.m.Y '.KERNEL_IN.' H:i', $su_last_date).' ('.PL_SITE_USERS_EDIT_IP.': '.($su_last_ip ? $su_last_ip : '-').')' : '-'));

        $layout->addField('', new sbLayoutInput('hidden', $su_reg_date, 'su_reg_date'));
        $layout->addField('', new sbLayoutInput('hidden', $su_last_date, 'su_last_date'));
        $layout->addField('', new sbLayoutInput('hidden', $su_last_ip, 'su_last_ip'));
    }

    $layout->addTab(PL_SITE_USERS_TAB2);
    $layout->addHeader(PL_SITE_USERS_TAB2);
    // Вкладка "Персональные"

    $layout->addField(PL_SITE_USERS_EDIT_FIO.sbGetGroupEditCheckbox('su_name', $edit_group), new sbLayoutInput('text', $su_name, 'su_name', '', 'style="width:100%;"'), false);
    
    $layout->addField('', new sbLayoutDelim());
    $layout->addField('', new sbLayoutLabel('<div class="hint_div">'.PL_SITE_USERS_PERS_FOTO_HINT.'</div>', '', '', false));
    
    $field = new sbLayoutImage($su_pers_foto, 'su_pers_foto', '', 'size="60%"');
    $field->mLocal = true;
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR.sbGetGroupEditCheckbox('su_pers_foto', $edit_group), $field);
    $layout->addField('', new sbLayoutDelim());
    
    $field = new sbLayoutDate($su_pers_birth, 'su_pers_birth', '', '');
    $field->mShowTime = false;
    $field->mDropButton = true;
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH.sbGetGroupEditCheckbox('su_pers_birth', $edit_group), $field);

    $fld = new sbLayoutSelect($GLOBALS['sb_sex_arr'][SB_CMS_LANG], 'su_pers_sex');
    $fld->mSelOptions = array($su_pers_sex);
    $layout->addField(PL_SITE_USERS_EDIT_SEX.sbGetGroupEditCheckbox('su_pers_sex', $edit_group), $fld);

    $layout->addField(PL_SITE_USERS_EDIT_PHONE.sbGetGroupEditCheckbox('su_pers_phone', $edit_group),new sbLayoutInput('text', $su_pers_phone, 'su_pers_phone', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE.sbGetGroupEditCheckbox('su_pers_mob_phone', $edit_group),new sbLayoutInput('text', $su_pers_mob_phone, 'su_pers_mob_phone', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP.sbGetGroupEditCheckbox('su_pers_zip', $edit_group),new sbLayoutInput('text', $su_pers_zip,'su_pers_zip', '', 'style="width:200px;" maxlength="6"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS.sbGetGroupEditCheckbox('su_pers_adress', $edit_group),new sbLayoutInput('text', $su_pers_adress, 'su_pers_adress', '', 'style="width:100%;"'), false);

    $field = new sbLayoutTextarea($su_pers_addition, 'su_pers_addition', '', 'style="width:100%;"');
    $field->mShowToolbar = true;
    $field->mShowEnlargeBtn = true;
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION.sbGetGroupEditCheckbox('su_pers_addition', $edit_group), $field);

    $layout->addTab(PL_SITE_USERS_TAB3);
    $layout->addHeader(PL_SITE_USERS_TAB3);
    // Вкладка "Корпоративные"

    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME.sbGetGroupEditCheckbox('su_work_name', $edit_group), new sbLayoutInput('text', $su_work_name, 'su_work_name', '', 'style="width:100%;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL.sbGetGroupEditCheckbox('su_work_unit', $edit_group), new sbLayoutInput('text', $su_work_unit, 'su_work_unit', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ.sbGetGroupEditCheckbox('su_work_position', $edit_group), new sbLayoutInput('text', $su_work_position, 'su_work_position', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE.sbGetGroupEditCheckbox('su_work_office_number', $edit_group), new sbLayoutInput('text', $su_work_office_number, 'su_work_office_number', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE.sbGetGroupEditCheckbox('su_work_phone', $edit_group), new sbLayoutInput('text', $su_work_phone, 'su_work_phone', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER.sbGetGroupEditCheckbox('su_work_phone_inner', $edit_group), new sbLayoutInput('text', $su_work_phone_inner, 'su_work_phone_inner', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX.sbGetGroupEditCheckbox('su_work_fax', $edit_group), new sbLayoutInput('text', $su_work_fax, 'su_work_fax', '', 'style="width:200px;"'), false);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL.sbGetGroupEditCheckbox('su_work_email', $edit_group), new sbLayoutInput('text', $su_work_email, 'su_work_email', '', 'style="width:200px;"'), false);

    $field = new sbLayoutTextarea($su_work_addition, 'su_work_addition', '', 'style="width:100%;"');
    $field->mShowToolbar = true;
    $field->mShowEnlargeBtn = true;
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION.sbGetGroupEditCheckbox('su_work_addition', $edit_group), $field);

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
    {
        // Вкладка "Рассылка"

        $layout->addTab(PL_SITE_USERS_TAB6);
        $layout->addHeader(PL_SITE_USERS_TAB6);

        $field = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'su_mail_lang');
        $field->mSelOptions = isset($su_mail_lang) ? array($su_mail_lang) : array();
        $layout->addField(PL_SITE_USERS_EDIT_LANG.sbGetGroupEditCheckbox('su_mail_lang', $edit_group), $field);
        $layout->addField('', new sbLayoutDelim());

        $html  = '<div style="margin-top:5px;"><input type="radio" value="0" name="su_mail_status" id="su_mail_status0" style="margin:0px;"'.(($su_mail_status == 0) ? 'checked':'').'>&nbsp;<label for="su_mail_status0">'.PL_SITE_USERS_EDIT_MAIL_STATUS_ACTIVE.'</label></div>';
        $html .= '<div style="margin-top:5px;"><input type="radio" value="1" name="su_mail_status" id="su_mail_status1" style="margin:0px;"'.(($su_mail_status == 1) ? 'checked':'').'>&nbsp;<label for="su_mail_status1">'.PL_SITE_USERS_EDIT_MAIL_STATUS_NO_ACTIVE.'</label></div>';
        $html .= '<div style="margin-top:5px;"><input type="radio" value="2" name="su_mail_status" id="su_mail_status2" style="margin:0px;"'.(($su_mail_status == 2) ? 'checked':'').'>&nbsp;<label for="su_mail_status2">'.PL_SITE_USERS_EDIT_MAIL_STATUS_NO_ACTIVE_BY_DATE.'</label>&nbsp;';

        $field = new sbLayoutDate($su_mail_date,'su_mail_date');
        $field->mDropButton = true;
        $field->mShowTime = false;

        $html .= $field->getField().'</div>';

        $hfld = new sbLayoutHTML($html);
        $hfld->mShowColon = false;

        $layout->addField(PL_SITE_USERS_EDIT_MAIL_STATUS.sbGetGroupEditCheckbox('su_mail_status', $edit_group),$hfld);
        $layout->addField('', new sbLayoutDelim());

        $res = sql_param_query('SELECT mail.m_name, link.link_el_id, cat.cat_title, cat.cat_id, cat.cat_level FROM sb_categs cat, sb_catlinks link, sb_maillist mail WHERE cat.cat_ident = "pl_maillist" AND link.link_cat_id = cat.cat_id AND mail.m_id = link.link_el_id ORDER BY cat.cat_left, mail.m_name');
        if($res)
        {
            $i = 0;
            $j = 1;
            $cur_cat_id = 0;
            $cur_cat_level = 0;
            $cur_cat_name = '';
            $cur_checked = false;

            $html = '';
            $m_html = '';
            foreach($res as $key => $value)
            {
                list($m_name, $link_el_id, $cat_name, $cat_id, $cat_level) = $value;

                if($cur_cat_id != $cat_id)
                {
                    if ($cur_cat_id != 0)
                    {
                        $html .= '<div style="margin-top:5px; margin-left:'.(15 * $cur_cat_level).'px;"><input type="checkbox" id="key_'.$i.'" value="1" '.($cur_checked ? 'checked' : '' ).' style="margin:0px;" align="middle" onclick="checkMaillist(this);">&nbsp;&nbsp;<label for="key_'.$i.'" style="color: #990033;">'.$cur_cat_name.'</label>'.$m_html.'</div>';
                    }

                    $i++;
                    $j = 1;
                    $cur_cat_id = $cat_id;
                    $cur_cat_level = $cat_level;
                    $cur_cat_name = $cat_name;
                    $cur_checked = false;
                    $m_html = '';
                }


                $checked = '';
                if (in_array($link_el_id, $su_mail_subscription))
                {
                    $cur_checked = true;
                    $checked = ' checked';
                }

                $m_html .= '<div style="margin-top:5px; margin-left:'.(15 * ($cat_level + 1)).'px;">
                    <input type="checkbox" style="margin:0px;" name="su_mail_subscription[]" id="key_'.$i.'_'.$j.'" value="'.$link_el_id.'"'.$checked.' onclick = "uncheckMaillist(this,\'key_'.$i.'\')">&nbsp;&nbsp;<label for="key_'.$i.'_'.$j.'">'.$m_name.'</label><br />
                </div>';

                $j++;
            }

            $html .= '<div style="margin-top:5px; margin-left:'.(15 * $cur_cat_level).'px;"><input type="checkbox" id="key_'.$i.'" value="1" '.($cur_checked ? 'checked' : '' ).' style="margin:0px;" align="middle" onclick="checkMaillist(this);">&nbsp;&nbsp;<label for="key_'.$i.'" style="color: #990033;">'.$cur_cat_name.'</label>'.$m_html.'</div>';

            $hfld = new sbLayoutHTML($html);
            $hfld->mShowColon = false;

            $layout->addField(PL_SITE_USERS_MAILLIST_TITLE_FILED.sbGetGroupEditCheckbox('su_mail_subscription', $edit_group), $hfld);
        }
        else
        {
            $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_MAILLIST_NO.'</div>';
            $layout->addField('', new sbLayoutHTML($html));
        }
    }

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $layout->addTab(PL_SITE_USERS_TAB4);
        $layout->addHeader(PL_SITE_USERS_TAB4);
        // Вкладка "Форум"

        $layout->addField(PL_SITE_USERS_EDIT_NICK.sbGetGroupEditCheckbox('su_forum_nick', $edit_group), new sbLayoutInput('text', $su_forum_nick, 'su_forum_nick', '', 'style="width:100%;"'), false);

        $field = new sbLayoutTextarea($su_forum_text, 'su_forum_text', '', 'style="width:100%;"');
        $field->mShowToolbar = true;
        $field->mShowEnlargeBtn = true;
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE.sbGetGroupEditCheckbox('su_forum_text', $edit_group), $field);
    }

    $layout->getPluginFields('pl_site_users', $_GET['id'], 'su_id', false, $edit_group);

    if (!$edit_group)
    {
        fVoting_Rating_Edit($layout, $_GET['id'], 'pl_site_users');
    }

    $layout->addButton('submit', KERNEL_SAVE, '', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'elems_edit') ? '' : 'disabled'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', ($edit_group ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"'));

    $layout->show();
}

function fSite_Users_Edit_Submit()
{
    $edit_group = sbIsGroupEdit();
    //  проверка прав доступа
    if ($edit_group)
    {
        if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_site_users'))
        {
            return;
        }
    }
    else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users'))
    {
        return;
    }

    if (!isset($_GET['id']))
       $_GET['id'] = '';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $user_row = array();
    $user_row = $layout->checkPluginFields('pl_site_users', ($edit_group ? $_GET['ids'] : $_GET['id']), 'su_id', false, $edit_group);

    if ($user_row === false)
    {
        $layout->deletePluginFieldsFiles();
        fSite_Users_Edit();
        return;
    }

    $su_mail_status = 1;
    $su_reg_date = time();
    $su_domains = array();
    $su_mail_subscription = array();

    $ch_su_status = $ch_su_active_date = $ch_su_domains = $ch_su_name = $ch_su_pers_foto = $ch_su_pers_birth = $ch_su_pers_sex = $ch_su_pers_phone =
    $ch_su_pers_mob_phone = $ch_su_pers_zip = $ch_su_pers_adress = $ch_su_pers_addition = $ch_su_work_name = $ch_su_work_unit = $ch_su_work_position =
    $ch_su_work_office_number = $ch_su_work_phone = $ch_su_work_phone_inner = $ch_su_work_fax = $ch_su_work_email = $ch_su_work_addition =
    $ch_su_mail_lang = $ch_su_mail_status = $ch_su_mail_subscription = $ch_su_forum_nick = $ch_su_forum_text = $su_status = 0;

    $su_name = $su_pers_birth = $su_pers_sex = $su_pers_phone = $su_pers_mob_phone = $su_pers_zip = $su_pers_adress = $su_pers_addition =
    $su_work_name = $su_work_unit = $su_work_position = $su_work_office_number = $su_work_phone = $su_work_phone_inner = $su_work_fax =
    $su_work_email = $su_work_addition = $su_pers_foto = $su_forum_nick = $su_forum_text = $su_mail_lang = $su_active_date = $su_mail_date = '';

    extract($_POST);

    if($su_active_date == '')
    {
        $su_active_date = 0;
    }
    else
    {
        $su_active_date = sb_datetoint($su_active_date);
    }

    if($su_mail_date == '')
    {
        $su_mail_date = null;
    }
    else
    {
        $su_mail_date = sb_datetoint($su_mail_date);
    }

    if($su_pers_birth == '')
    {
        $su_pers_birth = 0;
    }
    else
    {
        $su_pers_birth = sb_datetoint($su_pers_birth);
    }

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
    {
        if((!$edit_group || $edit_group && $ch_su_mail_status == 1) && $su_mail_date == '' && $su_mail_status == '2')
        {
            sb_show_message(PL_SITE_USERS_ERROR_MAIL_DATE, false, 'warning');
            fSite_Users_Edit();
            return;
        }

        if(!$edit_group && count($su_mail_subscription) > 0 && $su_email == '')
        {
            sb_show_message(PL_SITE_USERS_ERROR_MAIL_SUBSCRIPTION, false, 'warning');
            fSite_Users_Edit();
            return;
        }
    }

    $su_mail_subscription = implode(',', $su_mail_subscription);
    if((!$edit_group || $edit_group && $ch_su_domains == 1) && count($su_domains) == 0)
    {
        sb_show_message(PL_SITE_USERS_ERROR_DOMAINS, false, 'warning');
        fSite_Users_Edit();
        return;
    }

    if (!$edit_group)
        $pswrd_len = sbPlugins::getSetting('pl_site_users_password_length');

    $is_link = '';

    if(!$edit_group && preg_match('/[^A-z0-9А-я\-@\._!$%&+{}]/iu', $su_login))
    {
        sb_show_message(PL_SITE_USERS_ERROR_CHARACTER_LOGIN, false, 'warning');
        fSite_Users_Edit();
        return;
    }

    if(!$edit_group && $_GET['id'] == '' && $su_login != '')
    {
        $res = sql_param_query('SELECT COUNT(*) FROM sb_site_users WHERE su_login=?', $su_login);
        if($res && $res[0][0] > 0)
        {
            sb_show_message(PL_SITE_USERS_ERROR_LOGIN, false, 'warning');
            fSite_Users_Edit();
            return;
        }
    }
    elseif(!$edit_group && $_GET['id'] == '' && $su_login == '')
    {
        $res = sql_param_query('SELECT u.su_id, c.cat_id FROM sb_site_users u, sb_categs c, sb_catlinks l WHERE
            c.cat_ident ="pl_site_users" AND c.cat_id = l.link_cat_id AND l.link_el_id=u.su_id AND u.su_email = ?', $su_email);

        if($res && isset($res[0][1]))
        {
            if($pswrd_len == 0)
            {
                $is_link = $res[0][0].'|'.$res[0][1];
            }
            else
            {
                sb_show_message(PL_SITE_USERS_ERROR_EMAIL, false, 'warning');
                fSite_Users_Edit();
                return;
            }
        }
        $su_login = $su_email;
    }

    if (!$edit_group && $su_login == '' && $su_email == '')
    {
        sb_show_message(PL_SITE_USERS_EDIT_ERROR_LOGIN_E_MAIL, false, 'warning');
        fSite_Users_Edit();
        return;
    }

    if (!$edit_group && $pswrd_len != 0 && ($su_pass1 != $su_pass2 || ($su_pass1 != '' && sb_strlen($su_pass1) < $pswrd_len)))
    {
        sb_show_message(PL_SITE_USERS_EDIT_ERROR_PASSWORD, false, 'warning');
        fSite_Users_Edit();
        return;
    }

    if (!$edit_group)
        $su_pass = md5($su_pass1);

    // подключаем библиотеку работы с загрузкой файлов
    require_once(SB_CMS_LIB_PATH.'/sbUploader.inc.php');

    if ($edit_group && $ch_su_pers_foto == 1)
    {
        $su_pers_foto = array();
        $res = sql_query('SELECT su_pers_foto FROM sb_site_users WHERE su_id IN (?a)', $_GET['ids']);
        if ($res)
        {
            foreach($res as $key => $value)
            {
                $su_pers_foto[] = $value[0];
            }
        }
    }
    elseif(!$edit_group)
    {
        $su_pers_foto = '';
        $res = sql_param_query('SELECT su_pers_foto FROM sb_site_users WHERE su_id=?d', $_GET['id']);
        if ($res)
        {
            list($su_pers_foto) = $res[0];
        }
    }
    
    if((!$edit_group || $edit_group && $ch_su_pers_foto == 1) && isset($_POST['su_pers_foto']) && !isset($_POST['su_pers_foto_delete']))
    {
        $su_pers_foto = explode('/', $_POST['su_pers_foto']);
        $su_pers_foto = $su_pers_foto[count($su_pers_foto)-1];
    }
    elseif((!$edit_group || $edit_group && $ch_su_pers_foto == 1) && isset($_FILES['su_pers_foto']) && @is_uploaded_file($_FILES['su_pers_foto']['tmp_name']) && !isset($_POST['su_pers_foto_delete']))
    {
        //  загружаем фотографию пользователя
        $uploader = new sbUploader();
        $uploader->setMaxFileSize(sbPlugins::getSetting('pl_site_users_max_upload_size'));
        $uploader->setMaxImageSize(sbPlugins::getSetting('pl_site_users_max_upload_width'), sbPlugins::getSetting('pl_site_users_max_upload_height'));

        // сохраняем файл
        $success = $uploader->upload('su_pers_foto', array ('gif', 'jpeg', 'jpg', 'png'));
        $file_name = false;
        if ($success)
        {
            $file_name = $uploader->move(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/', time().'_'.$_FILES['su_pers_foto']['name']);
        }

        if (!$success || !$file_name)
        {
            sb_show_message($uploader->getError(), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_ERROR_SAVE_FILE, $_FILES['su_pers_foto']['name']), SB_MSG_WARNING);
            fSite_Users_Edit();
            return;
        }

        if ($edit_group && is_array($su_pers_foto) && count($su_pers_foto) > 0)
        {
            foreach ($su_pers_foto as $key => $value)
            {
                // удаляем фотографии пользователей
                if ($value != '' &&  $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value) && !$GLOBALS['sbVfs']->is_dir(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value))
                {
                    $GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value);
                }
            }
        }
        elseif(!$edit_group && $su_pers_foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto) && !$GLOBALS['sbVfs']->is_dir(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto))
        {
            // удаляем фотографию пользователя
            $GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto);
        }

        $su_pers_foto = $file_name;
    }
    elseif (!$edit_group && isset($_POST['su_pers_foto_delete']) && $_GET['id'] != '' && $su_pers_foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto) && !$GLOBALS['sbVfs']->is_dir(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto))
    {
        // удаляем фотографию пользователя
        $GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$su_pers_foto);
        $su_pers_foto = '';
    }
    elseif($edit_group && $ch_su_pers_foto == 1 && !isset($_FILES['su_pers_foto']['tmp_name']) && count($su_pers_foto) > 0)
    {
        foreach ($su_pers_foto as $key => $value)
        {
            // удаляем фотографии пользователей
            if ($value != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value) && !$GLOBALS['sbVfs']->is_dir(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value))
            {
                $GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$value);
            }
        }
        $su_pers_foto = '';
    }

    if($su_status == 2 || $su_status == 3)
    {
        $code = md5(time().uniqid());
    }
    else
    {
        $code = '';
    }

    $su_domains = implode(',', $su_domains);

    $row = array();

    if (!$edit_group)
    {
        $row['su_activation_code'] = $code;
        $row['su_pass'] = $su_pass;
        $row['su_reg_date'] = $su_reg_date;
        $row['su_email'] = $su_email;
        $row['su_login'] = $su_login;
    }

    if (!$edit_group || $edit_group && $ch_su_mail_status == 1)
    {
        $row['su_mail_date'] = $su_mail_date;
        $row['su_mail_status'] = $su_mail_status;
    }

    if (!$edit_group || $edit_group && $ch_su_name == 1)
        $row['su_name'] = $su_name;

    if (!$edit_group || $edit_group && $ch_su_domains == 1)
        $row['su_domains'] = $su_domains;

    if (!$edit_group || $edit_group && $ch_su_status == 1)
        $row['su_status'] = $su_status;

    if (!$edit_group || $edit_group && $ch_su_pers_foto == 1)
        $row['su_pers_foto'] = $su_pers_foto;

    if (!$edit_group || $edit_group && $ch_su_pers_phone == 1)
        $row['su_pers_phone'] = $su_pers_phone;

    if (!$edit_group || $edit_group && $ch_su_pers_mob_phone == 1)
        $row['su_pers_mob_phone'] = $su_pers_mob_phone;

    if (!$edit_group || $edit_group && $ch_su_pers_birth == 1)
        $row['su_pers_birth'] = $su_pers_birth;

    if (!$edit_group || $edit_group && $ch_su_pers_sex == 1)
        $row['su_pers_sex'] = $su_pers_sex;

    if (!$edit_group || $edit_group && $ch_su_pers_zip == 1)
        $row['su_pers_zip'] = $su_pers_zip;

    if (!$edit_group || $edit_group && $ch_su_pers_adress == 1)
        $row['su_pers_adress'] = $su_pers_adress;

    if (!$edit_group || $edit_group && $ch_su_pers_addition == 1)
        $row['su_pers_addition'] = $su_pers_addition;

    if (!$edit_group || $edit_group && $ch_su_work_name == 1)
        $row['su_work_name'] = $su_work_name;

    if (!$edit_group || $edit_group && $ch_su_work_phone == 1)
        $row['su_work_phone'] = $su_work_phone;

    if (!$edit_group || $edit_group && $ch_su_work_phone_inner == 1)
        $row['su_work_phone_inner'] = $su_work_phone_inner;

    if (!$edit_group || $edit_group && $ch_su_work_fax == 1)
        $row['su_work_fax'] = $su_work_fax;

    if (!$edit_group || $edit_group && $ch_su_work_email == 1)
        $row['su_work_email'] = $su_work_email;

    if (!$edit_group || $edit_group && $ch_su_work_addition == 1)
        $row['su_work_addition'] = $su_work_addition;

    if (!$edit_group || $edit_group && $ch_su_work_office_number == 1)
        $row['su_work_office_number'] = $su_work_office_number;

    if (!$edit_group || $edit_group && $ch_su_work_unit == 1)
        $row['su_work_unit'] = $su_work_unit;

    if (!$edit_group || $edit_group && $ch_su_work_position == 1)
        $row['su_work_position'] = $su_work_position;

    if (!$edit_group || $edit_group && $ch_su_forum_nick == 1)
        $row['su_forum_nick'] = $su_forum_nick;

    if (!$edit_group || $edit_group && $ch_su_forum_text == 1)
        $row['su_forum_text'] = $su_forum_text;

    if (!$edit_group || $edit_group && $ch_su_mail_lang == 1)
        $row['su_mail_lang'] = $su_mail_lang;

    if (!$edit_group || $edit_group && $ch_su_active_date == 1)
        $row['su_active_date'] = $su_active_date;

    if (!$edit_group || $edit_group && $ch_su_mail_subscription == 1)
        $row['su_mail_subscription'] = $su_mail_subscription;

    $row = array_merge($row, $user_row);

    if ($edit_group || $_GET['id'] != '')
    {
        if (!$edit_group)
        {
            $res = sql_param_query('SELECT su_pass, su_login, su_email, su_status, su_activation_code, su_reg_date FROM sb_site_users WHERE su_id=?d', $_GET['id']);
        }
        else
        {
            $res = true;
        }

        if ($res)
        {
            if (!$edit_group)
            {
                // редактирование
                list($old_pass, $old_login, $old_email, $old_status, $old_activation_code, $old_reg_date) = $res[0];

                if ($old_login == '')
                {
                    $old_login = $old_email;    
                }
                
                $row['su_reg_date'] = $old_reg_date;

                if ($su_pass1 == '')
                {
                    $row['su_pass'] = $old_pass;
                }

                if(($su_status == 2 || $su_status == 3) && ($old_status == 2 || $old_status == 3))
                {
                    $row['su_activation_code'] = $old_activation_code;
                }
            }

            if (!$edit_group)
            {
                sql_query('UPDATE sb_site_users SET ?a WHERE su_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_EDIT_OK, $old_login, ($su_login != '' ? $su_login : $su_email)));
            }
            else
            {
                if (count($row) > 0)
                    sql_query('UPDATE sb_site_users SET ?a WHERE su_id IN (?a)', $row, $_GET['ids'], PL_SITE_USERS_EDIT_GROUP_OK);
            }

            if (!$edit_group)
            {
                $row['su_id'] = intval($_GET['id']);
                $row['su_last_date'] = $su_last_date;
                $row['su_last_ip'] = $su_last_ip;

                $footer_ar = fCategs_Edit_Elem();
                if (!$footer_ar)
                {
                    sb_show_message(PL_SITE_USERS_EDIT_ERROR, false, 'warning');
                    sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_SYSTEMLOG_ERROR, ($su_login != '' ? $su_login : $su_email)), SB_MSG_WARNING);
                    $layout->deletePluginFieldsFiles();
                    fSite_Users_Edit();
                    return;
                }
                $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
                $footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

                fVoting_Rating_Edit_Submit($_GET['id'], 'pl_site_users');

                $html_str = fSite_Users_Get($row);
                $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
                $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "'.$footer_link_str.'";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                echo '<script>
                          sbReturnValue("refresh");
                      </script>';
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_SYSTEMLOG_ERROR, ($su_login != '' ? $su_login : $su_email)), SB_MSG_WARNING);
            $layout->deletePluginFieldsFiles();
            fSite_Users_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        sql_query('LOCK TABLES sb_site_users WRITE, sb_catlinks WRITE');

//      если длина пароля 0 и естьтакой email вставляем ссылку.
        if($is_link != '')
        {
            $is_link = explode('|', $is_link);

            sql_query('INSERT INTO sb_catlinks (link_cat_id, link_el_id, link_src_cat_id) VALUES (?d, ?d, ?d)', $_GET['cat_id'], $is_link[0], $is_link[1]);
            $id = sql_insert_id();
            if ($id)
            {
                $id = $is_link[0];
                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';
                $error = false;
            }

            sql_query('UNLOCK TABLES');
        }
        elseif (sql_param_query('INSERT INTO sb_site_users SET ?a', $row))
        {
            $id = sql_insert_id();
            sql_query('UNLOCK TABLES');

            if (fCategs_Add_Elem($id))
            {
                sb_add_system_message(sprintf(PL_SITE_USERS_ADD_OK, ($su_login != '' ? $su_login : $su_email)));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users WHERE su_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_ADD_ERROR, ($su_login != '' ? $su_login : $su_email)), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_ADD_SYSTEMLOG_ERROR, ($su_login != '' ? $su_login : $su_email)), SB_MSG_WARNING);
            $layout->deletePluginFieldsFiles();
            fSite_Users_Edit();
            return;
        }
        else
        {
            fVoting_Rating_Edit_Submit($id, 'pl_site_users');
        }
    }
}

function fSite_Users_Delete()
{
    if(!fCategs_Check_Rights($_GET['cat_id']))
    {
        return;
    }

    $id = intval($_GET['id']);

    fVoting_Delete($id, 'pl_site_users');
    fComments_Delete_Comment($id, 'pl_site_users');

    $res = sql_param_query('SELECT su_pers_foto FROM sb_site_users WHERE su_id=?d', $id);
    if ($res)
    {
        list($foto) = $res[0];

        if ($foto != '' && $GLOBALS['sbVfs']->exists(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$foto) && !$GLOBALS['sbVfs']->is_dir(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$foto))
            $GLOBALS['sbVfs']->delete(SB_SITE_USER_UPLOAD_PATH.'/pl_site_users/'.$foto);
    }
}

function fSite_Users_Cat_Edit()
{
    $cat_fields = array();
    $cat_fileds['cat_domains'] = array();
    $cat_fileds['cat_domains'][] = SB_COOKIE_DOMAIN;
    $cat_fields['cat_user_lang'] = 'ru';

    $cat_rights = '';

    if (isset($_GET['cat_id']))
    {
        if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        $res = sql_param_query('SELECT cat_title, cat_rubrik, cat_fields, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);

        if ($res)
        {
            list($cat_title, $cat_rubrik, $p_cat_fields, $cat_rights) = $res[0];
            if ($p_cat_fields != '')
            {
                $cat_fields = unserialize($p_cat_fields);
            }

            $_GET['cat_id_p'] = '';
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_CAT_ERROR_NO_CATEG, true, 'warning');
            return;
        }
    }
    else
    {
        if (!fCategs_Check_Rights($_GET['cat_id_p']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        $res = sql_param_query('SELECT cat_rubrik, cat_fields, cat_rights FROM sb_categs WHERE cat_id=?d', $_GET['cat_id_p']);
        if ($res)
        {
            $cat_title = '';
            list($cat_rubrik, $p_cat_fields, $cat_rights) = $res[0];
            if ($p_cat_fields != '')
            {
                $p_cat_fields = unserialize($p_cat_fields);

                if (isset($p_cat_fields['cat_status']))
                    $cat_fields['cat_status'] = $p_cat_fields['cat_status'];

                if (isset($p_cat_fields['cat_user_lang']))
                    $cat_fields['cat_user_lang'] = $p_cat_fields['cat_user_lang'];

                if (isset($p_cat_fields['cat_domains']))
                    $cat_fields['cat_domains'] = $p_cat_fields['cat_domains'];
            }

            $_GET['cat_id'] = '';
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_CAT_ERROR_NO_CATEG, true, 'warning');
            return;
        }
    }

    if (!isset($cat_fields['cat_status']))
    {
        $cat_fields['cat_status'] = 0;
    }

    echo '<script>
        function checkValues()
        {
            var cat_title = sbGetE("cat_title");
            if (cat_title.value == "")
            {
                alert("'.PL_SITE_USERS_EDIT_CAT_NO_TITLE_MSG.'");
                return false;
            }

            return true;
        }
    </script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_cat_edit_submit&cat_id='.$_GET['cat_id'].'&cat_id_p='.$_GET['cat_id_p'], 'thisDialog', 'post', 'checkValues()');
    $layout->mTitleWidth = 200;

    $layout->addTab(PL_SITE_USERS_EDIT_CAT_TAB1);
    $layout->addHeader(PL_SITE_USERS_EDIT_CAT_TAB1);
    $layout->addField(PL_SITE_USERS_EDIT_CAT_TITLE, new sbLayoutInput('text', $cat_title, 'cat_title', '', 'style="width:400px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $field = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'cat_fields[cat_user_lang]');
    $field->mSelOptions = isset($cat_fields['cat_user_lang']) ? array($cat_fields['cat_user_lang']) : array();
    $layout->addField(PL_SITE_USERS_EDIT_LANG, $field);

    $layout->addField('', new sbLayoutDelim());

    $html =  '<div style="margin-top:5px;"><input type="radio" value="0" name="cat_fields[cat_status]" id="cat_status0" style="margin:0px;"'.(($cat_fields['cat_status'] == 0) ? 'checked':'').'>&nbsp;<label for="cat_status0">'.PL_SITE_USERS_GET_STATUS_REG.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="1" name="cat_fields[cat_status]" id="cat_status1" style="margin:0px;"'.(($cat_fields['cat_status'] == 1) ? 'checked':'').'>&nbsp;<label for="cat_status1">'.PL_SITE_USERS_GET_STATUS_MOD.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="2" name="cat_fields[cat_status]" id="cat_status2" style="margin:0px;"'.(($cat_fields['cat_status'] == 2) ? 'checked':'').'>&nbsp;<label for="cat_status2">'.PL_SITE_USERS_GET_STATUS_EMAIL.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="3" name="cat_fields[cat_status]" id="cat_status3" style="margin:0px;"'.(($cat_fields['cat_status'] == 3) ? 'checked':'').'>&nbsp;<label for="cat_status3">'.PL_SITE_USERS_GET_STATUS_MOD_EMAIL.'</label></div>';
    $html .= '<div style="margin-top:5px;"><input type="radio" value="4" name="cat_fields[cat_status]" id="cat_status4" style="margin:0px;"'.(($cat_fields['cat_status'] == 4) ? 'checked':'').'>&nbsp;<label for="cat_status4">'.PL_SITE_USERS_GET_STATUS_BLOCK.'</label></div>';

    $layout->addField(PL_SITE_USERS_EDIT_CAT_STATUS, new sbLayoutHTML($html));

    require_once (SB_CMS_LIB_PATH.'/sbIDN.inc.php');
    $idn = new sbIDN();

    $html = '';
    foreach ($GLOBALS['sb_domains'] as $key => $value)
    {
        $html .= '<div style="margin-top:5px;"><input type="checkbox" value="'.$key.'" name="cat_fields[cat_domains][]" id="su_domains'.$key.'" style="margin:0px;"'.(isset($cat_fields['cat_domains']) && in_array($key, $cat_fields['cat_domains']) ? ' checked' : '').'>&nbsp;<label for="su_domains'.$key.'">'.$idn->decode($key).'</label></div>';
    }
    $layout->addField(PL_SITE_USERS_EDIT_CAT_DOMAINS, new sbLayoutHTML($html));

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_SITE_USERS_EDIT_CAT_ACTIVE, new sbLayoutInput('checkbox', '1', 'cat_rubrik', '', ($cat_rubrik == 1 ? 'checked' : '')));

    $layout->getPluginFields('pl_site_users', $_GET['cat_id'], '', true);

    $layout->addField('', new sbLayoutInput('hidden', $cat_rights, 'cat_rights'));

    $layout->addTab(PL_SITE_USERS_CAT_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_CAT_EDIT_TAB3);

    $fld = new sbLayoutInput('text', (isset($cat_fields['categs_moderate_email']) ? $cat_fields['categs_moderate_email'] : ''), 'categs_moderate_email', '', 'style="width:97%;"');
    $fld->mHTML = '<div class="hint_div">'.PL_SITE_USERS_CAT_EDIT_MODERATE_EMAIL_DESCR.'</div>';
    $layout->addField(PL_SITE_USERS_CAT_EDIT_MODERATE_EMAIL_FIELD, $fld);

    $layout->addField('', new sbLayoutDelim());

    include_once(SB_CMS_PL_PATH.'/pl_site_users/pl_site_users.inc.php');
    $layout->addField(PL_SITE_USERS_CAT_EDIT_MODERATE_FROM_SYSTEM_USERS, new sbLayoutHTML(fUsers_Get_Groups(isset($cat_fields['moderates_list']) ? $cat_fields['moderates_list'] : '', true)));

    $layout->addButton('submit', KERNEL_SAVE);
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_Cat_Edit_Submit()
{
    $cat_rubrik = 0;
    $cat_fields = array();
    $su_fields_temps = array();
    $groups = array();
    $users = array();

    extract($_POST);

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();

    $user_row = array();

    $user_row = $layout->checkPluginFields('pl_site_users', $_GET['cat_id'], '', true);
    if ($user_row === false)
    {
        $layout->deletePluginFieldsFiles();
        fSite_Users_Cat_Edit();
        return;
    }

    if (isset($_POST['categs_moderate_email']))
    {
        $cat_fields['categs_moderate_email'] = $_POST['categs_moderate_email'];

        $moderates_list = '';
        if (count($groups) > 0)
        {
            $moderates_list = 'g'.implode('^g', $groups);
        }

        if (count($users) > 0)
        {
            $moderates_list .= ($moderates_list != '' ? '^u' : 'u').implode('^u', $users);
        }

        $cat_fields['moderates_list'] = $moderates_list;
    }

    $cat_fields = array_merge($cat_fields, $user_row, $su_fields_temps);

    if($_GET['cat_id'] != '')
    {
        if (!fCategs_Check_Rights($_GET['cat_id']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        $cat_id = intval($_GET['cat_id']);

        //редактирование
        $res = sql_param_query('SELECT cat_left, cat_right FROM sb_categs WHERE cat_id=?d', $cat_id);
        if ($res)
        {
            $result = sql_param_query('UPDATE sb_categs SET cat_title=?, cat_rubrik=?d, cat_fields=? WHERE cat_id=?d', $cat_title, $cat_rubrik, serialize($cat_fields), $cat_id, sprintf(PL_SITE_USERS_EDIT_CAT_SYSLOG_EDIT_OK, $cat_title));
            if ($result)
            {
                list($cat_left, $cat_right) = $res[0];

                $count_res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident="pl_site_users" AND categs.cat_left >= '.$cat_left.' AND categs.cat_right <= '.$cat_right.' AND links.link_cat_id=categs.cat_id');
                if ($count_res)
                {
                    $cat_count = $count_res[0][0];
                }
                else
                {
                    $cat_count = 0;
                }

                echo '<script>
                    var res = new Object();
                    res.cat_title = "'.str_replace('"', '\\"', $cat_title).' ['.$cat_count.']";
                    res.cat_closed = 0;
                    res.cat_rubrik = '.intval($cat_rubrik).';
                    sbReturnValue(res);
                  </script>';
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_CAT_EDIT_ERROR, true, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_CAT_SYSLOG_EDIT_ERROR, $cat_title), SB_MSG_WARNING);
        }
    }
    else
    {
        if (!fCategs_Check_Rights($_GET['cat_id_p']))
        {
            sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
            return;
        }

        //добавление
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');
        $tree = new sbTree('pl_site_users');

        $fields = array();
        $fields['cat_title'] = $cat_title;
        $fields['cat_closed'] = 0;
        $fields['cat_rubrik'] = intval($cat_rubrik);
        $fields['cat_rights'] = $cat_rights;
        $fields['cat_fields'] = serialize($cat_fields);

        $cat_id = $tree->insertNode($_GET['cat_id_p'], $fields);
        if ($cat_id)
        {
            sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_CAT_SYSLOG_ADD_OK, $cat_title));

            echo '<script>
                var res = new Object();
                res.cat_id = '.$cat_id.';
                res.cat_title = "'.str_replace('"', '\\"', $cat_title).' [0]";
                res.cat_closed = 0;
                res.cat_rubrik = '.intval($cat_rubrik).';
                sbReturnValue(res);
              </script>';
        }
        else
        {
            sb_show_message(PL_SITE_USERS_EDIT_CAT_ADD_ERROR, true, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_EDIT_CAT_SYSLOG_ADD_ERROR, $cat_title), SB_MSG_WARNING);
        }
    }
}

/**
 * Функции управления макетами дизайна формы логина
 */
function fSite_Users_Login_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_login" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_login" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_Login_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Login_Temp_Get', 'pl_site_users_login_temp', 'pl_site_users_temp');

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_login_32.png';
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_login_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_login_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_login_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_login";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_login";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Login_Temp_Edit($htmlStr = '', $footerStr = '')
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_temp'))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sut_title, sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages
                                   FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);

        if ($result)
        {
            list($sut_title, $sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $result[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sut_fields_temps != '')
            $sut_fields_temps = unserialize($sut_fields_temps);
        else
            $sut_fields_temps = array();

        if ($sut_categs_temps != '')
            $sut_categs_temps = unserialize($sut_categs_temps);
        else
            $sut_categs_temps = array();

        if ($sut_messages != '')
            $sut_messages = unserialize($sut_messages);
        else
            $sut_messages = array();
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sut_title = '';
        $sut_form = PL_SITE_USERS_DESIGN_EDIT_FORM_DEFAULT;

        $sut_lang = SB_CMS_LANG;
        $sut_votes_id = 0;

        $sut_fields_temps = array();
        $sut_fields_temps['su_login'] = '{VALUE}';
        $sut_fields_temps['su_email'] = '{VALUE}';
        $sut_fields_temps['su_reg_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_name'] = '{VALUE}';
        $sut_fields_temps['su_pers_foto'] = '<img src="{VALUE}" width="{WIDTH}" height="{HEIGHT}" />';
        $sut_fields_temps['su_pers_birth'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_pers_sex'] = '{VALUE}';
        $sut_fields_temps['su_pers_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_mob_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_zip'] = '{VALUE}';
        $sut_fields_temps['su_pers_adress'] = '{VALUE}';
        $sut_fields_temps['su_pers_addition'] = '{VALUE}';
        $sut_fields_temps['su_work_name'] = '{VALUE}';
        $sut_fields_temps['su_work_unit'] = '{VALUE}';
        $sut_fields_temps['su_work_position'] = '{VALUE}';
        $sut_fields_temps['su_work_office_number'] = '{VALUE}';
        $sut_fields_temps['su_work_phone'] = '{VALUE}';
        $sut_fields_temps['su_work_phone_inner'] = '{VALUE}';
        $sut_fields_temps['su_work_fax'] = '{VALUE}';
        $sut_fields_temps['su_work_email'] = '{VALUE}';
        $sut_fields_temps['su_work_addition'] = '{VALUE}';
        $sut_fields_temps['su_forum_nick'] = '{VALUE}';
        $sut_fields_temps['su_forum_text'] = '{VALUE}';


        $sut_categs_temps = array();

        $sut_messages = array();
        $sut_messages['login'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_LOGIN_MES;
        $sut_messages['logout'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_LOGOUT_MES;
        $sut_messages['wrong_login'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_WRONG_LOGIN;
        $sut_messages['wrong_password'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_WRONG_PASSWORD;
        $sut_messages['wrong_domain'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_WRONG_DOMAIN;
        $sut_messages['premod_account'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_PREMOD_ACCOUNT;
        $sut_messages['email_account'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_EMAIL_ACCOUNT;
        $sut_messages['premod_email_account'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_PREMOD_EMAIL_ACCOUNT;
        $sut_messages['blocked_account'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_BLOCKED_ACCOUNT;
        $sut_messages['blocked_group'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_BLOCKED_GROUP;
        $sut_messages['wrong_ip'] = PL_SITE_USERS_DESIGN_EDIT_MESSAGES_WRONG_IP;
        $sut_messages['generate_errors'] = PL_SITE_USERS_GENERATE_ERRORS_TAG;


        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sut_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_login_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '98%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sut_title, 'sut_title', '', 'style="width:550px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sut_lang');
    $fld->mSelOptions = array($sut_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_form, 'sut_form', '', 'style="width:100%;height:350px;"');
    $fld->mTags = array(
        '{MESSAGE}',
        PL_SITE_USERS_DESIGN_EDIT_FORM_TEMP,
        PL_SITE_USERS_DESIGN_EDIT_LOGIN_TEMP,
        PL_SITE_USERS_DESIGN_EDIT_EMAIL_TEMP,
        PL_SITE_USERS_DESIGN_EDIT_PASSWORD_TEMP,
        PL_SITE_USERS_DESIGN_EDIT_AUTOLOGIN_TEMP,
    );
    $fld->mValues = array(
        PL_SITE_USERS_DESIGN_EDIT_MESSAGE_TAG,
        PL_SITE_USERS_DESIGN_EDIT_FORM_TAG,
        PL_SITE_USERS_DESIGN_EDIT_LOGIN_TAG,
        PL_SITE_USERS_DESIGN_EDIT_EMAIL_TAG,
        PL_SITE_USERS_DESIGN_EDIT_PASSWORD_TAG,
        PL_SITE_USERS_DESIGN_EDIT_AUTOLOGIN_TAG,
    );
    $settings = $_SESSION['sbPlugins']->getAllSettings(SB_COOKIE_DOMAIN);

    // тег авторизации через твиттер
    array_push($fld->mTags, PL_SITE_USERS_TWITTER_LINK);
    array_push($fld->mValues, PL_SITE_USERS_TWITTER_LINK_TAG);

    // тег авторизации через facebook
    array_push($fld->mTags, PL_SITE_USERS_FACEBOOK_LINK);
    array_push($fld->mValues, PL_SITE_USERS_FACEBOOK_LINK_TAG);

    // тег авторизации через Одноклассники
    array_push($fld->mTags, PL_SITE_USERS_ODK_LINK);
    array_push($fld->mValues, PL_SITE_USERS_ODK_LINK_TAG);

    // тег авторизации через Вконтакте
    array_push($fld->mTags, PL_SITE_USERS_VK_LINK);
    array_push($fld->mValues, PL_SITE_USERS_VK_LINK_TAG);

    // тег авторизации через LiveJournal
    array_push($fld->mTags, PL_SITE_USERS_LJ_LINK);
    array_push($fld->mValues, PL_SITE_USERS_LJ_LINK_TAG);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_FORM, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB2);

    $dop_tags = array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}');
    $dop_values = array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $tags = array('{VALUE}');
    $values = array(PL_SITE_USERS_DESIGN_EDIT_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_login'], 'sut_fields_temps[su_login]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_EMAIL}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_EMAIL));
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_email'], 'sut_fields_temps[su_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_LOGIN}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2));
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date'], 'sut_fields_temps[su_reg_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date'], 'sut_fields_temps[su_last_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_LAST_DATE, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_name'], 'sut_fields_temps[su_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_foto'], 'sut_fields_temps[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags);
    $fld->mValues = array_merge($values, array(SB_LAYOUT_VALUE_SIZE, SB_LAYOUT_VALUE_WIDTH, SB_LAYOUT_VALUE_HEIGHT), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_birth'], 'sut_fields_temps[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex'], 'sut_fields_temps[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_phone'], 'sut_fields_temps[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_mob_phone'], 'sut_fields_temps[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_zip'], 'sut_fields_temps[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_adress'], 'sut_fields_temps[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_addition'], 'sut_fields_temps[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name'], 'sut_fields_temps[su_work_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit'], 'sut_fields_temps[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position'], 'sut_fields_temps[su_work_position]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number'], 'sut_fields_temps[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone'], 'sut_fields_temps[su_work_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner'], 'sut_fields_temps[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax'], 'sut_fields_temps[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email'], 'sut_fields_temps[su_work_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition'], 'sut_fields_temps[su_work_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION, $fld);


    $forum_tags = array();
    $forum_tags_values = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick'], 'sut_fields_temps[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text'], 'sut_fields_temps[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
        $forum_tags_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_EDIT_NICK, PL_SITE_USERS_EDIT_SIGNATURE);
    }

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];

        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);

            if ($pd_fields)
            {
                if ($pd_fields[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', $dop_tags, $dop_values, false, '', '', '', false, true);
            }
        }

        if ($pd_categs != '')
        {
            $pd_categs = unserialize($pd_categs);

            if ($pd_categs)
            {
                if ($pd_categs[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB7.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_categs_temps, 'sut_', $dop_tags, $dop_values, true, '', '', '', false, true);
            }
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB3);

    $users_tags = array();
    $users_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $users_tags, $users_tags_values, false, false, false, false, true);

    $user_group_tags = array('-', '{SU_CAT_ID}');
    $user_group_tags_values = array(PL_SITE_USERS_EDIT_CAT_GROUP_TAG, PL_SITE_USERS_EDIT_CAT_ID_TAG);
    $layout->getPluginFieldsTags('pl_site_users', $user_group_tags, $user_group_tags_values, true, false, false, false, true);

    $fld = new sbLayoutTextarea($sut_messages['login'], 'sut_messages[login]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(
                    array(
                    '-', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}', '{SU_LOGOUT_LINK}',
                    '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                    '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}'),
                    $users_tags, $user_group_tags);
    $fld->mValues = array_merge(
                    array(
                    PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_EDIT_IP, PL_SITE_USERS_DESIGN_EDIT_LOGOUT_LINK,
                    PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                    PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG),
                    $users_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_MESSAGES_LOGIN, $fld);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_MESSAGES_LOGOUT, new sbLayoutTextarea($sut_messages['logout'], 'sut_messages[logout]', '', 'style="width:100%;height:100px;"'));

    $layout->addField('', new sbLayoutDelim());

    $users_tags = array('{SU_LOGIN}', '{SU_EMAIL}');
    $users_tags_values = array(PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $fld = new sbLayoutTextarea($sut_messages['wrong_login'], 'sut_messages[wrong_login]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_MESSAGES_ERRORS, $fld);

    $fld = new sbLayoutTextarea($sut_messages['wrong_password'], 'sut_messages[wrong_password]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['wrong_domain'], 'sut_messages[wrong_domain]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{DOMAIN}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_EDIT_DOMAIN), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['premod_account'], 'sut_messages[premod_account]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['email_account'], 'sut_messages[email_account]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['premod_email_account'], 'sut_messages[premod_email_account]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['blocked_account'], 'sut_messages[blocked_account]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['blocked_group'], 'sut_messages[blocked_group]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['wrong_ip'], 'sut_messages[wrong_ip]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    //Блок генерируемых ошибок
    $fld = new sbLayoutTextarea(isset($sut_messages['generate_errors']) ? $sut_messages['generate_errors'] : '', 'sut_messages[generate_errors]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField(PL_SITE_USERS_GENERATE_ERRORS_TITLE, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_Login_Temp_Submit()
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_temp'))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sut_lang = SB_CMS_LANG;
    $sut_fields_temps = array();
    $sut_categs_temps = array();
    $sut_messages = array();

    extract($_POST);

    if ($sut_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fSite_Users_Login_Temp_Edit();
        return;
    }

    $row = array();
    $row['sut_title'] = $sut_title;
    $row['sut_lang'] = $sut_lang;
    $row['sut_form'] = $sut_form;
    $row['sut_fields_temps'] = serialize($sut_fields_temps);
    $row['sut_categs_temps'] = serialize($sut_categs_temps);
    $row['sut_messages'] = serialize($sut_messages);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sut_title FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps SET ?a WHERE sut_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_DESIGN_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_site_users_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fSite_Users_Login_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sut_id'] = intval($_GET['id']);

            $html_str = fSite_Users_Login_Temp_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fSite_Users_Login_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_EDIT_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Login_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_site_users_temps', $id);
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_ADD_OK, $sut_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps WHERE sut_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_DESIGN_ADD_ERROR, $sut_title), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_ADD_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Login_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_Login_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_login" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_login" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна формы регистрации
 */
function fSite_Users_Reg_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_reg" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_reg" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_Reg_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Reg_Temp_Get', 'pl_site_users_reg_temp', 'pl_site_users_reg_temp');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_reg_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_reg_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_reg_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_reg_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_reg";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_reg";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Reg_Temp_Edit($htmlStr = '', $footerStr = '', $update = false)
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], ($update ? 'pl_site_users_update_temp' : 'pl_site_users_reg_temp')))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sut_title, sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages
                                   FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($result)
        {
            list($sut_title, $sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $result[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sut_fields_temps != '')
            $sut_fields_temps = unserialize($sut_fields_temps);
        else
            $sut_fields_temps = array();

        if ($sut_categs_temps != '')
            $sut_categs_temps = unserialize($sut_categs_temps);
        else
            $sut_categs_temps = array();

        if ($sut_messages != '')
            $sut_messages = unserialize($sut_messages);
        else
            $sut_messages = array();

        if($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
        {
            if(!isset($sut_fields_temps['su_mail_subscription_val']))
                $sut_fields_temps['su_mail_subscription_val'] = '';
            if(!isset($sut_fields_temps['su_mail_lang_val']))
                $sut_fields_temps['su_mail_lang_val'] = '';
            if(!isset($sut_fields_temps['su_mail_status_val']))
                $sut_fields_temps['su_mail_status_val'] = '';
            if(!isset($sut_fields_temps['su_mail_date_val']))
                $sut_fields_temps['su_mail_date_val'] = '';
            if(!isset($sut_fields_temps['su_mail_subscription']))
                $sut_fields_temps['su_mail_subscription'] = '';
            if(!isset($sut_fields_temps['su_mail_lang']))
                $sut_fields_temps['su_mail_lang'] = '';
            if(!isset($sut_fields_temps['su_mail_lang']))
                $sut_fields_temps['su_mail_lang'] = '';
            if(!isset($sut_fields_temps['su_mail_lang_opt']))
                $sut_fields_temps['su_mail_lang_opt'] = '';
            if(!isset($sut_fields_temps['su_mail_status']))
                $sut_fields_temps['su_mail_status'] = '';
            if(!isset($sut_fields_temps['su_mail_status_opt']))
                $sut_fields_temps['su_mail_status_opt'] = '';
            if(!isset($sut_fields_temps['su_mail_date']))
                $sut_fields_temps['su_mail_date'] ='';
        }
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sut_title = $sut_form = '';
        $sut_lang = SB_CMS_LANG;

        $sut_fields_temps = array();
        $sut_fields_temps['su_date_format'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_login'] = PL_SITE_USERS_DESIGN_REG_EDIT_LOGIN_FIELD_VALUE;
        $sut_fields_temps['su_email'] = PL_SITE_USERS_DESIGN_REG_EDIT_EMAIL_FIELD_VALUE;
        $sut_fields_temps['su_pass'] = PL_SITE_USERS_DESIGN_REG_EDIT_PASS_FIELD_VALUE;
        $sut_fields_temps['su_pass2'] = PL_SITE_USERS_DESIGN_REG_EDIT_PASS2_FIELD_VALUE;
        $sut_fields_temps['su_captcha'] = PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_FIELD_VALUE;
        $sut_fields_temps['su_captcha_img'] = PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_IMG_FIELD_VALUE;
        $sut_fields_temps['su_name'] = PL_SITE_USERS_DESIGN_REG_EDIT_NAME_FIELD_VALUE;
        $sut_fields_temps['su_pers_foto'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_FOTO_FIELD_VALUE;
        $sut_fields_temps['su_pers_birth'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_BIRTH_FIELD_VALUE;
        $sut_fields_temps['su_pers_sex'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_FIELD_VALUE;
        $sut_fields_temps['su_pers_sex_opt'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_OPTION_VALUE;
        $sut_fields_temps['su_pers_phone'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_PHONE_FIELD_VALUE;
        $sut_fields_temps['su_pers_mob_phone'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_MOB_PHONE_FIELD_VALUE;
        $sut_fields_temps['su_pers_zip'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ZIP_FIELD_VALUE;
        $sut_fields_temps['su_pers_adress'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADRESS_FIELD_VALUE;
        $sut_fields_temps['su_pers_addition'] = PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADDITION_FIELD_VALUE;
        $sut_fields_temps['su_work_name'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_NAME_FIELD_VALUE;
        $sut_fields_temps['su_work_unit'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_UNIT_FIELD_VALUE;
        $sut_fields_temps['su_work_position'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_POSITION_FIELD_VALUE;
        $sut_fields_temps['su_work_office_number'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_OFFICE_NUMBER_FIELD_VALUE;
        $sut_fields_temps['su_work_phone'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_FIELD_VALUE;
        $sut_fields_temps['su_work_phone_inner'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_INNER_FIELD_VALUE;
        $sut_fields_temps['su_work_fax'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_FAX_FIELD_VALUE;
        $sut_fields_temps['su_work_email'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_EMAIL_FIELD_VALUE;
        $sut_fields_temps['su_work_addition'] = PL_SITE_USERS_DESIGN_REG_EDIT_WORK_ADDITION_FIELD_VALUE;
        $sut_fields_temps['su_forum_nick'] = PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_NICK_FIELD_VALUE;
        $sut_fields_temps['su_forum_text'] = PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_TEXT_FIELD_VALUE;
        $sut_fields_temps['su_mail_lang'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_VALUE;
        $sut_fields_temps['su_mail_lang_opt'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_OPT_VALUE;
        $sut_fields_temps['su_mail_status'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_VALUE;
        $sut_fields_temps['su_mail_status_opt'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_OPT_VALUE;
        $sut_fields_temps['su_mail_date'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_DATE_FIELD_VALUE;
        $sut_fields_temps['su_mail_subscription'] = 0;
        $sut_fields_temps['su_select_start'] = PL_SITE_USERS_DESIGN_REG_EDIT_SELECT_START_FIELD_VALUE;
        $sut_fields_temps['su_select_end'] = PL_SITE_USERS_DESIGN_REG_EDIT_SELECT_END_FIELD_VALUE;

        $sut_fields_temps['su_login_val'] = '{VALUE}';
        $sut_fields_temps['su_email_val'] = '{VALUE}';
        $sut_fields_temps['su_reg_date_val'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';

        if ($update)
        {
            $sut_fields_temps['su_last_date_val'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
            $sut_fields_temps['su_status_val'] = '{VALUE}';
            $sut_fields_temps['su_active_date_val'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        }

        $sut_fields_temps['su_pass_val'] = '{VALUE}';
        $sut_fields_temps['su_name_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_foto_val'] = '<img src="{VALUE}" width="{WIDTH}" height="{HEIGHT}" />';
        $sut_fields_temps['su_pers_birth_val'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_pers_sex_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_phone_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_mob_phone_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_zip_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_adress_val'] = '{VALUE}';
        $sut_fields_temps['su_pers_addition_val'] = '{VALUE}';
        $sut_fields_temps['su_work_name_val'] = '{VALUE}';
        $sut_fields_temps['su_work_unit_val'] = '{VALUE}';
        $sut_fields_temps['su_work_position_val'] = '{VALUE}';
        $sut_fields_temps['su_work_office_number_val'] = '{VALUE}';
        $sut_fields_temps['su_work_phone_val'] = '{VALUE}';
        $sut_fields_temps['su_work_phone_inner_val'] = '{VALUE}';
        $sut_fields_temps['su_work_fax_val'] = '{VALUE}';
        $sut_fields_temps['su_work_email_val'] = '{VALUE}';
        $sut_fields_temps['su_work_addition_val'] = '{VALUE}';
        $sut_fields_temps['su_forum_nick_val'] = '{VALUE}';
        $sut_fields_temps['su_forum_text_val'] = '{VALUE}';
        $sut_fields_temps['su_mail_subscription_val'] = '{VALUE}';
        $sut_fields_temps['su_mail_lang_val'] = '{VALUE}';
        $sut_fields_temps['su_mail_status_val'] = '{VALUE}';
        $sut_fields_temps['su_mail_date_val'] = '{VALUE}';

        $sut_fields_temps['su_login_need'] = 1;
        $sut_fields_temps['su_email_need'] = 1;
        $sut_fields_temps['su_pass_need'] = 1;
        $sut_fields_temps['su_pass2_need'] = 1;

        $sut_categs_temps = array();

        $sut_messages = array();
        $sut_messages['registrate'] = ($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_MESSAGES_REGISTRATE : PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_REGISTRATE);
        $sut_messages['activate'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_ACTIVATE;
        $sut_messages['activate_mod'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_ACTIVATE_MOD;
        $sut_messages['activate_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_ACTIVATE_ERROR;
        $sut_messages['login_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_LOGIN_ERROR;
        $sut_messages['email_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_EMAIL_ERROR;
        $sut_messages['pass_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_PASS_ERROR;
        $sut_messages['login_min_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_LOGIN_MIN_ERROR;
        $sut_messages['pass_min_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_PASS_MIN_ERROR;
        $sut_messages['fields_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_FIELDS_ERROR;
        $sut_messages['file_ext_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_FILE_EXT_ERROR;
        $sut_messages['file_size_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_FILE_SIZE_ERROR;
        $sut_messages['image_size_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_IMAGE_SIZE_ERROR;
        $sut_messages['file_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_FILE_ERROR;
        $sut_messages['captcha_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_CAPTCHA_ERROR;
        $sut_messages['registrate_error'] = ($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_MESSAGES_REGISTRATE_ERROR : PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_REGISTRATE_ERROR);

        if ($update)
        {
            $sut_messages['logged_error'] = PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_LOGGED_ERROR;

            $sut_messages['user_subj'] = PL_SITE_USERS_DESIGN_UPDATE_EDIT_MAIL_USER_SUBJ;
            $sut_messages['user_text'] = PL_SITE_USERS_DESIGN_UPDATE_EDIT_MAIL_USER_TEXT;
            $sut_messages['admin_subj'] = PL_SITE_USERS_DESIGN_UPDATE_EDIT_MAIL_ADMIN_SUBJ;
            $sut_messages['admin_text'] = PL_SITE_USERS_DESIGN_UPDATE_EDIT_MAIL_ADMIN_TEXT;
        }
        else
        {
            $sut_messages['user_subj'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_USER_SUBJ;
            $sut_messages['user_text'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_USER_TEXT;
            $sut_messages['admin_subj'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_ADMIN_SUBJ;
            $sut_messages['admin_text'] = PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_ADMIN_TEXT;
        }

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sut_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_'.($update ? 'update' : 'reg').'_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB1);

    // Название макета дизайна
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sut_title, 'sut_title', '', 'style="width:550px;"', true));

    $layout->addField('', new sbLayoutDelim());

    // Формат даты
    $fld = new sbLayoutTextarea($sut_fields_temps['su_date_format'], 'sut_fields_temps[su_date_format]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_DATE_FORMAT, $fld);

    // Язык
    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sut_lang');
    $fld->mSelOptions = array($sut_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_REG_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_REG_EDIT_TAB2);

    $tags = array('{VALUE}');
    $values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    // Логин
    $fld = new sbLayoutTextarea($sut_fields_temps['su_login'], 'sut_fields_temps[su_login]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_LOGIN_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);

    if ($update)
    {
        $layout->addField(PL_SITE_USERS_EDIT_LOGIN2.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_login_need]" id="su_login_need"'.(isset($sut_fields_temps['su_login_need']) && $sut_fields_temps['su_login_need'] == 1 ? 'checked' : '').'><label for="su_login_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }
    else
    {
        $layout->addField(PL_SITE_USERS_EDIT_LOGIN2.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_login_need]" id="su_login_need"'.(isset($sut_fields_temps['su_login_need']) && $sut_fields_temps['su_login_need'] == 1 ? 'checked' : '').' onclick="if (!sbGetE(\'su_email_need\').checked) this.checked=true;"><label for="su_login_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }

    // E-mail
    $fld = new sbLayoutTextarea($sut_fields_temps['su_email'], 'sut_fields_temps[su_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_EMAIL_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);

    if ($update)
    {
        $layout->addField(PL_SITE_USERS_EDIT_EMAIL.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_email_need]" id="su_email_need"'.(isset($sut_fields_temps['su_email_need']) && $sut_fields_temps['su_email_need'] == 1 ? 'checked' : '').'><label for="su_email_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }
    else
    {
        $layout->addField(PL_SITE_USERS_EDIT_EMAIL.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_email_need]" id="su_email_need"'.(isset($sut_fields_temps['su_email_need']) && $sut_fields_temps['su_email_need'] == 1 ? 'checked' : '').' onclick="if (!sbGetE(\'su_login_need\').checked) this.checked=true;"><label for="su_email_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
    }

    // Пароль
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pass'], 'sut_fields_temps[su_pass]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PASS_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_PASS.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pass_need]" id="su_pass_need"'.(isset($sut_fields_temps['su_pass_need']) && $sut_fields_temps['su_pass_need'] == 1 ? 'checked' : '').'><label for="su_pass_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Повтор пароля
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pass2'], 'sut_fields_temps[su_pass2]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PASS2_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_PASS2.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pass2_need]" id="su_pass2_need"'.(isset($sut_fields_temps['su_pass2_need']) && $sut_fields_temps['su_pass2_need'] == 1 ? 'checked' : '').'><label for="su_pass2_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Поле ввода кода с картинки (CAPTCHA)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_captcha'], 'sut_fields_temps[su_captcha]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_FIELD_VALUE);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_TAG, $fld);

    // Картинка с кодом (CAPTCHA)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_captcha_img'], 'sut_fields_temps[su_captcha_img]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_IMG_FIELD_VALUE);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_IMG_TAG, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    // Ф.И.О.
    $fld = new sbLayoutTextarea($sut_fields_temps['su_name'], 'sut_fields_temps[su_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_NAME_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_name_need]" id="su_name_need"'.(isset($sut_fields_temps['su_name_need']) && $sut_fields_temps['su_name_need'] == 1 ? 'checked' : '').'><label for="su_name_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Фото
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_foto'], 'sut_fields_temps[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_FOTO_FIELD_VALUE);
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_foto_need]" id="su_pers_foto_need"'.(isset($sut_fields_temps['su_pers_foto_need']) && $sut_fields_temps['su_pers_foto_need'] == 1 ? 'checked' : '').'><label for="su_pers_foto_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // День рождения
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_birth'], 'sut_fields_temps[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_BIRTH_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_birth_need]" id="su_pers_birth_need"'.(isset($sut_fields_temps['su_pers_birth_need']) && $sut_fields_temps['su_pers_birth_need'] == 1 ? 'checked' : '').'><label for="su_pers_birth_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Пол
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex'], 'sut_fields_temps[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_FIELD_VALUE, '{OPTIONS}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
    $layout->addField(PL_SITE_USERS_EDIT_SEX.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_sex_need]" id="su_pers_sex_need"'.(isset($sut_fields_temps['su_pers_sex_need']) && $sut_fields_temps['su_pers_sex_need'] == 1 ? 'checked' : '').'><label for="su_pers_sex_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex_opt'], 'sut_fields_temps[su_pers_sex_opt]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_OPTION_VALUE, '{OPT_VALUE}', '{OPT_TEXT}', '{OPT_SELECTED}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_SPRAV_ID, SB_LAYOUT_SPRAV_TITLE, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_SELECTED_TAG);
    $layout->addField('', $fld);

    // Телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_phone'], 'sut_fields_temps[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_PHONE_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_phone_need]" id="su_pers_phone_need"'.(isset($sut_fields_temps['su_pers_phone_need']) && $sut_fields_temps['su_pers_phone_need'] == 1 ? 'checked' : '').'><label for="su_pers_phone_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Мобильный телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_mob_phone'], 'sut_fields_temps[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_MOB_PHONE_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_mob_phone_need]" id="su_pers_mob_phone_need"'.(isset($sut_fields_temps['su_pers_mob_phone_need']) && $sut_fields_temps['su_pers_mob_phone_need'] == 1 ? 'checked' : '').'><label for="su_pers_mob_phone_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Индекс
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_zip'], 'sut_fields_temps[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ZIP_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_zip_need]" id="su_pers_zip_need"'.(isset($sut_fields_temps['su_pers_zip_need']) && $sut_fields_temps['su_pers_zip_need'] == 1 ? 'checked' : '').'><label for="su_pers_zip_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Адрес
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_adress'], 'sut_fields_temps[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADRESS_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_adress_need]" id="su_pers_adress_need"'.(isset($sut_fields_temps['su_pers_adress_need']) && $sut_fields_temps['su_pers_adress_need'] == 1 ? 'checked' : '').'><label for="su_pers_adress_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Доп. информация
    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_addition'], 'sut_fields_temps[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADDITION_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_pers_addition_need]" id="su_pers_addition_need"'.(isset($sut_fields_temps['su_pers_addition_need']) && $sut_fields_temps['su_pers_addition_need'] == 1 ? 'checked' : '').'><label for="su_pers_addition_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Аккаунт в соц. сети
    $fld = new sbLayoutTextarea(isset($sut_fields_temps['su_pers_soc_akk'])? $sut_fields_temps['su_pers_soc_akk'] : '<input type="checkbox" value="{SOC_AKK_ID}" name="su_pers_soc_akk[]" id="su_pers_soc_akk_{SOC_AKK_ID}" checked="checked"><label for="su_pers_soc_akk_{SOC_AKK_ID}">{SOC_AKK_LABEL}</label><br>', 'sut_fields_temps[su_pers_soc_akk]', '', 'style="width:100%;height:50px;"');
    $layout->addField(PL_SITE_USERS_EDIT_SOCIAL_AKK, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    // Название компании
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name'], 'sut_fields_temps[su_work_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_NAME_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_name_need]" id="su_work_name_need"'.(isset($sut_fields_temps['su_work_name_need']) && $sut_fields_temps['su_work_name_need'] == 1 ? 'checked' : '').'><label for="su_work_name_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Отдел / Подразделение
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit'], 'sut_fields_temps[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_UNIT_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_unit_need]" id="su_work_unit_need"'.(isset($sut_fields_temps['su_work_unit_need']) && $sut_fields_temps['su_work_unit_need'] == 1 ? 'checked' : '').'><label for="su_work_unit_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Должность
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position'], 'sut_fields_temps[su_work_position]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_POSITION_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_position_need]" id="su_work_position_need"'.(isset($sut_fields_temps['su_work_position_need']) && $sut_fields_temps['su_work_position_need'] == 1 ? 'checked' : '').'><label for="su_work_position_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Номер офиса
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number'], 'sut_fields_temps[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_OFFICE_NUMBER_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_office_number_need]" id="su_work_office_number_need"'.(isset($sut_fields_temps['su_work_office_number_need']) && $sut_fields_temps['su_work_office_number_need'] == 1 ? 'checked' : '').'><label for="su_work_office_number_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone'], 'sut_fields_temps[su_work_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_phone_need]" id="su_work_phone_need"'.(isset($sut_fields_temps['su_work_phone_need']) && $sut_fields_temps['su_work_phone_need'] == 1 ? 'checked' : '').'><label for="su_work_phone_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Внутренний номер
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner'], 'sut_fields_temps[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_INNER_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_phone_inner_need]" id="su_work_phone_inner_need"'.(isset($sut_fields_temps['su_work_phone_inner_need']) && $sut_fields_temps['su_work_phone_inner_need'] == 1 ? 'checked' : '').'><label for="su_work_phone_inner_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Факс
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax'], 'sut_fields_temps[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_FAX_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_fax_need]" id="su_work_fax_need"'.(isset($sut_fields_temps['su_work_fax_need']) && $sut_fields_temps['su_work_fax_need'] == 1 ? 'checked' : '').'><label for="su_work_fax_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // E-mail
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email'], 'sut_fields_temps[su_work_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_EMAIL_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_email_need]" id="su_work_email_need"'.(isset($sut_fields_temps['su_work_email_need']) && $sut_fields_temps['su_work_email_need'] == 1 ? 'checked' : '').'><label for="su_work_email_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    // Доп. информация
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition'], 'sut_fields_temps[su_work_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_WORK_ADDITION_FIELD_VALUE), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_work_addition_need]" id="su_work_addition_need"'.(isset($sut_fields_temps['su_work_addition_need']) && $sut_fields_temps['su_work_addition_need'] == 1 ? 'checked' : '').'><label for="su_work_addition_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

    $forum_tags = array();
    $forum_values = array();

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        // Ник на форуме
        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick'], 'sut_fields_temps[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_NICK_FIELD_VALUE), $tags);
        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_forum_nick_need]" id="su_forum_nick_need"'.(isset($sut_fields_temps['su_forum_nick_need']) && $sut_fields_temps['su_forum_nick_need'] == 1 ? 'checked' : '').'><label for="su_forum_nick_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

        // Подпись на форуме
        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text'], 'sut_fields_temps[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_TEXT_FIELD_VALUE), $tags);
        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_forum_text_need]" id="su_forum_text_need"'.(isset($sut_fields_temps['su_forum_text_need']) && $sut_fields_temps['su_forum_text_need'] == 1 ? 'checked' : '').'><label for="su_forum_text_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

        $forum_tags = array('-', PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_NICK_FIELD_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_TEXT_FIELD_TAG);
        $forum_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_NICK_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_TEXT_TAG);
    }

    $mail_tags = array();
    $mail_values = array();

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB6.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        if(isset($sut_fields_temps['su_mail_subscription']) && (intval($sut_fields_temps['su_mail_subscription']) == $sut_fields_temps['su_mail_subscription'] || trim($sut_fields_temps['su_mail_subscription']) == ''))
        {
            fMaillist_Design_Get($layout, $sut_fields_temps['su_mail_subscription'], 'sut_fields_temps[su_mail_subscription]', isset($sut_fields_temps['su_mail_subscription_need']) ? $sut_fields_temps['su_mail_subscription_need'] : 0);
        }
        elseif(isset($sut_fields_temps['su_mail_subscription']) && is_string($sut_fields_temps['su_mail_subscription']) && trim($sut_fields_temps['su_mail_subscription']) != '')
        {
            //  Листы рассылок
            $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_subscription'], 'sut_fields_temps[su_mail_subscription]', '', 'style="width:100%;height:50px;"');
            $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_FIELD_VALUE, '{MAIL_NAME}'));
            $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, PL_SITE_USERS_EDIT_MAIL_NAME));
            $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_MAILLIST.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_mail_subscription_need]" id="su_mail_subscription_need"'.(isset($sut_fields_temps['su_mail_subscription_need']) && $sut_fields_temps['su_mail_subscription_need'] == 1 ? 'checked' : '').'><label for="su_mail_subscription_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);
        }

        //  Язык рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_lang'], 'sut_fields_temps[su_mail_lang]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_VALUE, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_OPTIONS_VALUE);
        $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
        $layout->addField(PL_SITE_USERS_EDIT_LANG.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_mail_lang_need]" id="su_mail_lang_need"'.(isset($sut_fields_temps['su_mail_lang_need']) && $sut_fields_temps['su_mail_lang_need'] == 1 ? 'checked' : '').'><label for="su_mail_lang_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

        //  Опции списка языка рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_lang_opt'], 'sut_fields_temps[su_mail_lang_opt]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_OPT_VALUE, '{TITLE}'), $tags);
        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_FIELD_NAME), $values);
        $layout->addField('', $fld);

        //  Статус рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_status'], 'sut_fields_temps[su_mail_status]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_VALUE, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_OPTIONS_VALUE);
        $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
        $layout->addField(PL_SITE_USERS_EDIT_MAIL_STATUS.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_mail_status_need]" id="su_mail_status_need"'.(isset($sut_fields_temps['su_mail_status_need']) && $sut_fields_temps['su_mail_status_need'] == 1 ? 'checked' : '').'><label for="su_mail_status_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

        //  Опции списка статуса рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_status_opt'], 'sut_fields_temps[su_mail_status_opt]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_OPT_VALUE, '{TITLE}'), $tags);
        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_FIELD_NAME), $values);
        $layout->addField('', $fld);

        //  Дата возобновления рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_date'], 'sut_fields_temps[su_mail_date]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_DATE_FIELD_VALUE), $tags);
        $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
        $layout->addField(PL_SITE_USERS_EDIT_NO_ACTIVE_DATE.'<br><input type="checkbox" value="1" name="sut_fields_temps[su_mail_date_need]" id="su_mail_date_need"'.(isset($sut_fields_temps['su_mail_date_need']) && $sut_fields_temps['su_mail_date_need'] == 1 ? 'checked' : '').'><label for="su_mail_date_need">'.SB_LAYOUT_PLUGIN_FIELDS_REQ.'</label>', $fld);

        $mail_tags = array('-', PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_FIELD_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_LANG_FIELD_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_STATUS_FIELD_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAILLIST_DATE_FIELD_TAG);
        $mail_values = array(PL_SITE_USERS_TAB6, PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_SUBSCRIPTION_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_LANG_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_STATUS_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_DATE_TAG);
    }

    $pd_fields = array();
    $pd_categs = array();

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];
        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);
            if (count($pd_fields) > 0)
            {
                if ($pd_fields[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                // Пользовательские поля
                $layout->addPluginInputFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', '', array(), array(), false, true, '', '', false, true);
            }
        }
        else
        {
            $pd_fields = array();
        }

        if ($pd_categs != '')
        {
            $pd_categs = unserialize($pd_categs);
        }
        else
        {
            $pd_categs = array();
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_REG_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_REG_EDIT_TAB1);

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $user_tags, $user_tags_values, false, true, true, false, true, true);

    // Форма
    $fld = new sbLayoutTextarea($sut_form, 'sut_form', '', 'style="width:100%;height:250px;"');
    $fld->mTags = array_merge(array('-',
                        '{MESSAGE}',
                        ($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_FORM_TEMP : PL_SITE_USERS_DESIGN_REG_EDIT_FORM_TEMP),
                        PL_SITE_USERS_DESIGN_REG_EDIT_LOGIN_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_EMAIL_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PASS_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PASS2_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_FIELD_TAG,
                        '{SU_CAPTCHA_IMG}',
                        '-',
                        PL_SITE_USERS_DESIGN_REG_EDIT_NAME_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_FOTO_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_BIRTH_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_PHONE_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_MOB_PHONE_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ZIP_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADRESS_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADDITION_FIELD_TAG,
                        PL_SITE_USERS_DESINE_REG_EDIT_PERS_SOCIAL_AKK_FIELD_TAG,
                        '-',
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_NAME_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_UNIT_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_POSITION_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_OFFICE_NUMBER_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_INNER_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_FAX_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_EMAIL_FIELD_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_ADDITION_FIELD_TAG),
                        $forum_tags,
                        $mail_tags,
                        $user_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_TAB1, PL_SITE_USERS_DESIGN_EDIT_MESSAGE_TAG, PL_SITE_USERS_DESIGN_EDIT_FORM_TAG, PL_SITE_USERS_DESIGN_EDIT_LOGIN_TAG, PL_SITE_USERS_DESIGN_EDIT_EMAIL_TAG, PL_SITE_USERS_DESIGN_EDIT_PASSWORD_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PASSWORD2_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_CAPTCHA_IMG_TAG,
                        PL_SITE_USERS_TAB2, PL_SITE_USERS_DESIGN_REG_EDIT_NAME_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_FOTO_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_BIRTH_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_PHONE_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_MOB_PHONE_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ZIP_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADRESS_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADDITION_TAG, PL_SITE_USERS_DESINE_REG_EDIT_SOCIAL_AKK_TAG,
                        PL_SITE_USERS_TAB3, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_NAME_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_UNIT_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_POSITION_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_OFFICE_NUMBER_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_INNER_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_FAX_TAG,
                        PL_SITE_USERS_DESIGN_REG_EDIT_WORK_EMAIL_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_WORK_ADDITION_TAG),
                        $forum_values, $mail_values, $user_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_FORM, $fld);

    // Конец выделения обязательных полей
    $fld = new sbLayoutTextarea($sut_fields_temps['su_select_start'], 'sut_fields_temps[su_select_start]', '', 'style="width:100%;height:50px;"');
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_SELECT_START, $fld);

    // Начало выделения обязательных полей
    $fld = new sbLayoutTextarea($sut_fields_temps['su_select_end'], 'sut_fields_temps[su_select_end]', '', 'style="width:100%;height:50px;"');
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_SELECT_END, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB2);

    $dop_tags = array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}');
    $dop_values = array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $tags = array('{VALUE}');
    $values = array(PL_SITE_USERS_DESIGN_EDIT_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_login_val'], 'sut_fields_temps[su_login_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_EMAIL}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_EMAIL));
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_email_val'], 'sut_fields_temps[su_email_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_LOGIN}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2));
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date_val'], 'sut_fields_temps[su_reg_date_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);

    if ($update)
    {
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sut_fields_temps['su_status_val'], 'sut_fields_temps[su_status_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_active_date_val'], 'sut_fields_temps[su_active_date_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
        $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
        $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_ACTIVE, $fld);
    }

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_name_val'], 'sut_fields_temps[su_name_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_foto_val'], 'sut_fields_temps[su_pers_foto_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags);
    $fld->mValues = array_merge($values, array(SB_LAYOUT_VALUE_SIZE, SB_LAYOUT_VALUE_WIDTH, SB_LAYOUT_VALUE_HEIGHT), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_birth_val'], 'sut_fields_temps[su_pers_birth_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex_val'], 'sut_fields_temps[su_pers_sex_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_phone_val'], 'sut_fields_temps[su_pers_phone_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_mob_phone_val'], 'sut_fields_temps[su_pers_mob_phone_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_zip_val'], 'sut_fields_temps[su_pers_zip_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_adress_val'], 'sut_fields_temps[su_pers_adress_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_addition_val'], 'sut_fields_temps[su_pers_addition_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);
    
    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name_val'], 'sut_fields_temps[su_work_name_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit_val'], 'sut_fields_temps[su_work_unit_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position_val'], 'sut_fields_temps[su_work_position_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number_val'], 'sut_fields_temps[su_work_office_number_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_val'], 'sut_fields_temps[su_work_phone_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner_val'], 'sut_fields_temps[su_work_phone_inner_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax_val'], 'sut_fields_temps[su_work_fax_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email_val'], 'sut_fields_temps[su_work_email_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition_val'], 'sut_fields_temps[su_work_addition_val]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION, $fld);

    $forum_tags = array();
    $forum_values = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick_val'], 'sut_fields_temps[su_forum_nick_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text_val'], 'sut_fields_temps[su_forum_text_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
        $forum_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_EDIT_NICK, PL_SITE_USERS_EDIT_SIGNATURE);
    }

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_maillist'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB6.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        //  Листы рассылок
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_subscription_val'], 'sut_fields_temps[su_mail_subscription_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_MAILLIST, $fld);

        //  Язык рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_lang_val'], 'sut_fields_temps[su_mail_lang_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_LANG, $fld);

        //  Статус рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_status_val'], 'sut_fields_temps[su_mail_status_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_MAIL_STATUS, $fld);

        //  Дата возобновления рассылки
        $fld = new sbLayoutTextarea($sut_fields_temps['su_mail_date_val'], 'sut_fields_temps[su_mail_date_val]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NO_ACTIVE_DATE, $fld);

        $mail_tags = array('-', '{SU_MAILLISTS}', '{SU_MAILLISTS_LANG}', '{SU_MAILLISTS_STATUS}', '{SU_MAILLISTS_DATE}');
        $mail_values = array(PL_SITE_USERS_TAB6, PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_MAILLIST, PL_SITE_USERS_EDIT_LANG, PL_SITE_USERS_EDIT_STATUS, PL_SITE_USERS_EDIT_NO_ACTIVE_DATE);
    }

    if (count($pd_fields) > 0)
    {
        if ($pd_fields[0]['type'] != 'tab')
        {
            $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
            $layout->addField('', new sbLayoutHTML($html, true));
            $layout->addField('', new sbLayoutDelim());
        }

        $layout->addPluginFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', $dop_tags, $dop_values, false, '', '', '_val', false, true);
    }

    if (count($pd_categs) > 0)
    {
        if ($pd_categs[0]['type'] != 'tab')
        {
            $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB7.'</div>';
            $layout->addField('', new sbLayoutHTML($html, true));
            $layout->addField('', new sbLayoutDelim());
        }

        $layout->addPluginFieldsTemps('pl_site_users', $sut_categs_temps, 'sut_', $dop_tags, $dop_values, true, '', '', '_val', false, true);
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB3);

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $user_tags, $user_tags_values, false, false, false, false, true);

    $user_group_tags = array('-', '{SU_CAT_ID}');
    $user_group_tags_values = array(PL_SITE_USERS_EDIT_CAT_GROUP_TAG, PL_SITE_USERS_EDIT_CAT_ID_TAG);
    $layout->getPluginFieldsTags('pl_site_users', $user_group_tags, $user_group_tags_values, true, false, false, false, true);

    if ($update)
    {
        $tags = array('-', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_STATUS}', '{SU_ACTIVE_DATE}', '{SU_ID}', '-', '{SU_NAME}',
                  '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}',
                  '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}', '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}',
                  '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}');

        $values = array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, PL_SITE_USERS_DESIGN_DATA_GET_ACTIVE,
                        PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_FOTO, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE,
                        PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                        PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE,
                        PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX,
                        PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION);
    }
    else
    {
        $tags = array('-', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_REG_DATE}', '{SU_ID}', '-', '{SU_NAME}',
                  '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}',
                  '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}', '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}',
                  '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}');

        $values = array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_EDIT_ID,
                        PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_FOTO, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE,
                        PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                        PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE,
                        PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX,
                        PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION);
    }

    $fld = new sbLayoutTextarea($sut_messages['registrate'], 'sut_messages[registrate]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField($update ? PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_UPDATE_TITLE : PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_REGISTRATE_TITLE, $fld);

    $fld = new sbLayoutTextarea($sut_messages['activate'], 'sut_messages[activate]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_ACTIVATE_TITLE, $fld);

    $fld = new sbLayoutTextarea($sut_messages['activate_mod'], 'sut_messages[activate_mod]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MESSAGES_ACTIVATE_MOD_TITLE, $fld);
    
    //Аккаунты в соц. сетях
    $fld = new sbLayoutTextarea(isset($sut_messages['social_akk'])? $sut_messages['social_akk'] : '', 'sut_messages[social_akk]', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_SITE_USERS_EDIT_SOCIAL_AKK, $fld);

    $layout->addField('', new sbLayoutDelim());

    $users_tags = array('{SU_LOGIN}', '{SU_EMAIL}');
    $users_tags_values = array(PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $fld = new sbLayoutTextarea($sut_messages['activate_error'], 'sut_messages[activate_error]', '', 'style="width:100%;height:60px;"');
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_MESSAGES_ERRORS, $fld);

    if ($update)
    {
        $fld = new sbLayoutTextarea($sut_messages['logged_error'], 'sut_messages[logged_error]', '', 'style="width:100%;height:60px;"');
        $fld->mTags = $users_tags;
        $fld->mValues = $users_tags_values;
        $layout->addField('', $fld);
    }

    $fld = new sbLayoutTextarea($sut_messages['login_error'], 'sut_messages[login_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['email_error'], 'sut_messages[email_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['pass_error'], 'sut_messages[pass_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_PASS}', '{SU_PASS2}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PASS, PL_SITE_USERS_DESIGN_REG_EDIT_PASS2_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['login_min_error'], 'sut_messages[login_min_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_LOGIN_MIN}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_LOGIN_MIN_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['pass_min_error'], 'sut_messages[pass_min_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_PASS_MIN}', '{SU_PASS}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_PASS_MIN_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_PASS), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['fields_error'], 'sut_messages[fields_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['file_ext_error'], 'sut_messages[file_ext_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_FILE_EXT}', '{SU_FILE}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_FILE_EXT_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_FILE_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['file_size_error'], 'sut_messages[file_size_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_FILE_SIZE}', '{SU_FILE}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_FILE_SIZE_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_FILE_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['image_size_error'], 'sut_messages[image_size_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_IMAGE_WIDTH}', '{SU_IMAGE_HEIGHT}', '{SU_FILE}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_IMAGE_WIDTH_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_IMAGE_HEIGHT_TAG, PL_SITE_USERS_DESIGN_REG_EDIT_FILE_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['file_error'], 'sut_messages[file_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array_merge(array('{SU_FILE}'), $users_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_DESIGN_REG_EDIT_FILE_TAG), $users_tags_values);
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['captcha_error'], 'sut_messages[captcha_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $fld = new sbLayoutTextarea($sut_messages['registrate_error'], 'sut_messages[registrate_error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = $users_tags;
    $fld->mValues = $users_tags_values;
    $layout->addField('', $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_TAB);
    $layout->addHeader(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_TAB);

    if ($update)
    {
        $tags = array('-', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_PASS}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_STATUS}', '{SU_ACTIVE_DATE}', '{SU_ID}', '-', '{SU_NAME}',
                      '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}',
                      '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}', '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}',
                      '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}');

        $values = array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_DESIGN_REG_EDIT_PASS, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, PL_SITE_USERS_DESIGN_DATA_GET_ACTIVE,
                        PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_FOTO, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE,
                        PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                        PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE,
                        PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX,
                        PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION);
    }
    else
    {
        $tags = array('-', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_PASS}', '{SU_REG_DATE}', '{SU_ID}', '<a href=\'{SU_ACTIVATION_PAGE}?su_code={SU_ACTIVATION_CODE}\'>{SU_ACTIVATION_PAGE}?su_code={SU_ACTIVATION_CODE}</a>', '-', '{SU_NAME}',
                      '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}',
                      '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}', '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}',
                      '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}');

        $values = array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_DESIGN_REG_EDIT_PASS, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_DESIGN_REG_EDIT_ACTIVATE_CODE_TAG,
                        PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_FOTO, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE,
                        PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                        PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE,
                        PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX,
                        PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION);
    }

    $fld = new sbLayoutTextarea($sut_messages['user_subj'], 'sut_messages[user_subj]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_USER_SUBJ_TITLE, $fld);

    $fld = new sbLayoutTextarea($sut_messages['user_text'], 'sut_messages[user_text]', '', 'style="width:100%;height:200px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_USER_TEXT_TITLE, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_messages['admin_subj'], 'sut_messages[admin_subj]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_ADMIN_SUBJ_TITLE, $fld);

    $fld = new sbLayoutTextarea($sut_messages['admin_text'], 'sut_messages[admin_text]', '', 'style="width:100%;height:200px;"');
    $fld->mTags = array_merge($tags, $forum_tags, $mail_tags, $user_tags, $user_group_tags);
    $fld->mValues = array_merge($values, $forum_values, $mail_values, $user_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_ADMIN_TEXT_TITLE, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_Reg_Temp_Submit($update = false)
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], ($update ? 'pl_site_users_update_temp' : 'pl_site_users_reg_temp')))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sut_lang = SB_CMS_LANG;
    $sut_fields_temps = array();
    $sut_categs_temps = array();
    $sut_messages = array();

    extract($_POST);

    if ($sut_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        if ($update)
            fSite_Users_Update_Temp_Edit();
        else
            fSite_Users_Reg_Temp_Edit();
        return;
    }

    $row = array();
    $row['sut_title'] = $sut_title;
    $row['sut_lang'] = $sut_lang;
    $row['sut_form'] = $sut_form;
    $row['sut_fields_temps'] = serialize($sut_fields_temps);
    $row['sut_categs_temps'] = serialize($sut_categs_temps);
    $row['sut_messages'] = serialize($sut_messages);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sut_title FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps SET ?a WHERE sut_id=?d', $row, $_GET['id'], sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_OK : PL_SITE_USERS_DESIGN_REG_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_site_users_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_SYSTEMLOG_ERROR : PL_SITE_USERS_DESIGN_REG_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                if ($update)
                    fSite_Users_Update_Temp_Edit();
                else
                    fSite_Users_Reg_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sut_id'] = intval($_GET['id']);

            if ($update)
                $html_str = fSite_Users_Update_Temp_Get($row);
            else
                $html_str = fSite_Users_Reg_Temp_Get($row);

            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                if ($update)
                    fSite_Users_Update_Temp_Edit($html_str, $footer_str);
                else
                    fSite_Users_Reg_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_EDIT_SYSTEMLOG_ERROR : PL_SITE_USERS_DESIGN_REG_EDIT_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            if ($update)
                fSite_Users_Update_Temp_Edit();
            else
                fSite_Users_Reg_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sb_add_system_message(sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_ADD_OK : PL_SITE_USERS_DESIGN_REG_ADD_OK, $sut_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps WHERE sut_id="'.$id.'"');
            }
        }

        if($error)
        {
            sb_show_message(sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_ADD_ERROR : PL_SITE_USERS_DESIGN_REG_ADD_ERROR, $sut_title), false, 'warning');
            sb_add_system_message(sprintf($update ? PL_SITE_USERS_DESIGN_UPDATE_ADD_SYSTEMLOG_ERROR : PL_SITE_USERS_DESIGN_REG_ADD_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            if ($update)
                fSite_Users_Update_Temp_Edit();
            else
                fSite_Users_Reg_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_Reg_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_reg" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_reg" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна формы изменения данных
 */
function fSite_Users_Update_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_update" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_update" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_Update_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Update_Temp_Get', 'pl_site_users_update_temp', 'pl_site_users_update_temp');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_update_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_update_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_update_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_update_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_update";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_update";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Update_Temp_Edit($htmlStr = '', $footerStr = '')
{
    fSite_Users_Reg_Temp_Edit($htmlStr, $footerStr, true);
}

function fSite_Users_Update_Temp_Submit()
{
    fSite_Users_Reg_Temp_Submit(true);
}

function fSite_Users_Update_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_update" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_update" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна формы логина
 */
function fSite_Users_Remind_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_remind" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_remind" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_Remind_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Remind_Temp_Get', 'pl_site_users_remind_temp', 'pl_site_users_remind_temp');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_remind_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_remind_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_remind_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_remind_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_remind";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_remind";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Remind_Temp_Edit($htmlStr = '', $footerStr = '')
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_remind_temp'))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sut_title, sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages
                                   FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);

        if ($result)
        {
            list($sut_title, $sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages) = $result[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sut_fields_temps != '')
            $sut_fields_temps = unserialize($sut_fields_temps);
        else
            $sut_fields_temps = array();

        if ($sut_categs_temps != '')
            $sut_categs_temps = unserialize($sut_categs_temps);
        else
            $sut_categs_temps = array();

        if ($sut_messages != '')
            $sut_messages = unserialize($sut_messages);
        else
            $sut_messages = array();
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sut_title = '';
        $sut_form = PL_SITE_USERS_DESIGN_REMIND_EDIT_FORM_DEFAULT;

        $sut_lang = SB_CMS_LANG;
        $sut_votes_id = 0;

        $sut_fields_temps = array();
        $sut_fields_temps['su_login'] = '{VALUE}';
        $sut_fields_temps['su_email'] = '{VALUE}';
        $sut_fields_temps['su_reg_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_name'] = '{VALUE}';
        $sut_fields_temps['su_pers_foto'] = '<img src="{VALUE}" width="{WIDTH}" height="{HEIGHT}" />';
        $sut_fields_temps['su_pers_birth'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_pers_sex'] = '{VALUE}';
        $sut_fields_temps['su_pers_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_mob_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_zip'] = '{VALUE}';
        $sut_fields_temps['su_pers_adress'] = '{VALUE}';
        $sut_fields_temps['su_pers_addition'] = '{VALUE}';
        $sut_fields_temps['su_work_name'] = '{VALUE}';
        $sut_fields_temps['su_work_unit'] = '{VALUE}';
        $sut_fields_temps['su_work_position'] = '{VALUE}';
        $sut_fields_temps['su_work_office_number'] = '{VALUE}';
        $sut_fields_temps['su_work_phone'] = '{VALUE}';
        $sut_fields_temps['su_work_phone_inner'] = '{VALUE}';
        $sut_fields_temps['su_work_fax'] = '{VALUE}';
        $sut_fields_temps['su_work_email'] = '{VALUE}';
        $sut_fields_temps['su_work_addition'] = '{VALUE}';
        $sut_fields_temps['su_forum_nick'] = '{VALUE}';
        $sut_fields_temps['su_forum_text'] = '{VALUE}';

        $sut_categs_temps = array();

        $sut_messages = array();
        $sut_messages['ok'] = PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_OK;
        $sut_messages['error'] = PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_ERROR;
        $sut_messages['mail_subj'] = PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_MAIL_SUBJ_DEFAULT;
        $sut_messages['mail_text'] = PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_MAIL_TEXT_DEFAULT;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sut_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_remind_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '98%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sut_title, 'sut_title', '', 'style="width:550px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sut_lang');
    $fld->mSelOptions = array($sut_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_form, 'sut_form', '', 'style="width:100%;height:350px;"');
    $fld->mTags = array('{MESSAGE}', PL_SITE_USERS_DESIGN_REMIND_EDIT_FORM_TEMP, PL_SITE_USERS_DESIGN_REMIND_EDIT_LOGIN_TEMP, PL_SITE_USERS_DESIGN_REMIND_EDIT_EMAIL_TEMP);
    $fld->mValues = array(PL_SITE_USERS_DESIGN_EDIT_MESSAGE_TAG, PL_SITE_USERS_DESIGN_EDIT_FORM_TAG, PL_SITE_USERS_DESIGN_EDIT_LOGIN_TAG, PL_SITE_USERS_DESIGN_EDIT_EMAIL_TAG);
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_FORM, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB2);

    $dop_tags = array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}');
    $dop_values = array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $tags = array('{VALUE}');
    $values = array(PL_SITE_USERS_DESIGN_EDIT_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_login'], 'sut_fields_temps[su_login]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_EMAIL}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_EMAIL));
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_email'], 'sut_fields_temps[su_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_LOGIN}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2));
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date'], 'sut_fields_temps[su_reg_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date'], 'sut_fields_temps[su_last_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_LAST_DATE, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_name'], 'sut_fields_temps[su_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_foto'], 'sut_fields_temps[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags);
    $fld->mValues = array_merge($values, array(SB_LAYOUT_VALUE_SIZE, SB_LAYOUT_VALUE_WIDTH, SB_LAYOUT_VALUE_HEIGHT), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_birth'], 'sut_fields_temps[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex'], 'sut_fields_temps[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_phone'], 'sut_fields_temps[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_mob_phone'], 'sut_fields_temps[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_zip'], 'sut_fields_temps[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_adress'], 'sut_fields_temps[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_addition'], 'sut_fields_temps[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name'], 'sut_fields_temps[su_work_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit'], 'sut_fields_temps[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position'], 'sut_fields_temps[su_work_position]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number'], 'sut_fields_temps[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone'], 'sut_fields_temps[su_work_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner'], 'sut_fields_temps[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax'], 'sut_fields_temps[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email'], 'sut_fields_temps[su_work_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition'], 'sut_fields_temps[su_work_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION, $fld);

    $forum_tags = array();
    $forum_tags_values = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick'], 'sut_fields_temps[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text'], 'sut_fields_temps[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}');
        $forum_tags_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_EDIT_NICK, PL_SITE_USERS_EDIT_SIGNATURE);
    }

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];

        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);

            if ($pd_fields)
            {
                if ($pd_fields[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', $dop_tags, $dop_values, false, '', '', '', false, true);
            }
        }

        if ($pd_categs != '')
        {
            $pd_categs = unserialize($pd_categs);

            if ($pd_categs)
            {
                if ($pd_categs[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB7.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_categs_temps, 'sut_', $dop_tags, $dop_values, true, '', '', '', false, true);
            }
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB3);

    $users_tags = array();
    $users_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $users_tags, $users_tags_values, false, false, false, false, true);

    $user_group_tags = array('-', '{SU_CAT_ID}');
    $user_group_tags_values = array(PL_SITE_USERS_EDIT_CAT_GROUP_TAG, PL_SITE_USERS_EDIT_CAT_ID_TAG);
    $layout->getPluginFieldsTags('pl_site_users', $user_group_tags, $user_group_tags_values, true, false, false, false, true);

    $fld = new sbLayoutTextarea($sut_messages['ok'], 'sut_messages[ok]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(
                    array(
                    '-', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_NEW_PASS}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}',
                    '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                    '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}'),
                    $users_tags, $user_group_tags);
    $fld->mValues = array_merge(
                    array(
                    PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_DESIGN_REMIND_EDIT_PASS_TAG, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_EDIT_IP,
                    PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                    PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG),
                    $users_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_OK_TITLE, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_messages['error'], 'sut_messages[error]', '', 'style="width:100%;height:60px;"');
    $fld->mTags = array('{SU_LOGIN}', '{SU_EMAIL}');
    $fld->mValues = array(PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);
    $layout->addField(PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_ERROR_TITLE, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_TAB);
    $layout->addHeader(PL_SITE_USERS_DESIGN_REG_EDIT_MAIL_TAB);

    $fld = new sbLayoutTextarea($sut_messages['mail_subj'], 'sut_messages[mail_subj]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(
                    array(
                    '-', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}',
                    '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                    '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}'),
                    $users_tags, $user_group_tags);
    $fld->mValues = array_merge(
                    array(
                    PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_EDIT_IP,
                    PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                    PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG),
                    $users_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_MAIL_SUBJ, $fld);

    $fld = new sbLayoutTextarea($sut_messages['mail_text'], 'sut_messages[mail_text]', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array_merge(
                    array(
                    '-', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_NEW_PASS}', '{SU_REG_DATE}', '{SU_LAST_DATE}', '{SU_LAST_IP}',
                    '-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}',
                    '-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}'),
                    $users_tags, $user_group_tags);
    $fld->mValues = array_merge(
                    array(
                    PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_DESIGN_REMIND_EDIT_PASS_TAG, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_EDIT_IP,
                    PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION,
                    PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG),
                    $users_tags_values, $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_REMIND_EDIT_MESSAGES_MAIL_TEXT, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_Remind_Temp_Submit()
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_remind_temp'))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sut_lang = SB_CMS_LANG;
    $sut_fields_temps = array();
    $sut_categs_temps = array();
    $sut_messages = array();

    extract($_POST);

    if ($sut_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fSite_Users_Remind_Temp_Edit();
        return;
    }

    $row = array();
    $row['sut_title'] = $sut_title;
    $row['sut_lang'] = $sut_lang;
    $row['sut_form'] = $sut_form;
    $row['sut_fields_temps'] = serialize($sut_fields_temps);
    $row['sut_categs_temps'] = serialize($sut_categs_temps);
    $row['sut_messages'] = serialize($sut_messages);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sut_title FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps SET ?a WHERE sut_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_DESIGN_REMIND_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_site_users_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_REMIND_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fSite_Users_Remind_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sut_id'] = intval($_GET['id']);

            $html_str = fSite_Users_Remind_Temp_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fSite_Users_Remind_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_REMIND_EDIT_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Remind_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_site_users_temps', $id);
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_REMIND_ADD_OK, $sut_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps WHERE sut_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_DESIGN_REMIND_ADD_ERROR, $sut_title), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_REMIND_ADD_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Remind_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_Remind_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_remind" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_remind" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна формы вывода данных
 */
function fSite_Users_Data_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_data" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_data" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_Data_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Data_Temp_Get', 'pl_site_users_data_temp', 'pl_site_users_data_temp');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_data_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_data_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_data_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_data_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_data";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_data";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Data_Temp_Edit($htmlStr = '', $footerStr = '')
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_data_temp'))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sut_title, sut_lang, sut_form, sut_fields_temps, sut_categs_temps, sut_messages, sut_checked, sut_votes_id, sut_comments_id
                                   FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);

        if ($result)
        {
            list($sut_title, $sut_lang, $sut_form, $sut_fields_temps, $sut_categs_temps, $sut_messages, $sut_checked, $sut_votes_id, $sut_comments_id) = $result[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sut_fields_temps != '')
            $sut_fields_temps = unserialize($sut_fields_temps);
        else
            $sut_fields_temps = array();

        if ($sut_categs_temps != '')
            $sut_categs_temps = unserialize($sut_categs_temps);
        else
            $sut_categs_temps = array();

        if ($sut_messages != '')
            $sut_messages = unserialize($sut_messages);
        else
            $sut_messages = array();

        if ($sut_checked != '')
            $sut_checked = explode(' ', $sut_checked);
        else
            $sut_checked = array();

        if(!isset($sut_fields_temps['su_forum_messages_link']))
            $sut_fields_temps['su_forum_messages_link'] = '';
        if(!isset($sut_fields_temps['su_forum_count_msg']))
            $sut_fields_temps['su_forum_count_msg'] = '';
        if(!isset($sut_fields_temps['author_msg_page']))
            $sut_fields_temps['author_msg_page'] = '';
        if(!isset($sut_fields_temps['su_change_date']))
            $sut_fields_temps['su_change_date'] = '';
    }
    elseif (count($_POST) > 0)
    {
        $sut_checked = array();
        $sut_comments_id = 0;
        $sut_votes_id = 0;

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sut_checked = array();
        $sut_title = $sut_form = '';
        $sut_votes_id = 0;
        $sut_comments_id = 0;
        $sut_lang = SB_CMS_LANG;

        $sut_fields_temps = array();
        $sut_fields_temps['su_login'] = '{VALUE}';
        $sut_fields_temps['su_email'] = '{VALUE}';
        $sut_fields_temps['su_status'] = '{VALUE}';
        $sut_fields_temps['su_reg_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sut_fields_temps['su_active_date'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_name'] = '{VALUE}';
        $sut_fields_temps['su_pers_foto'] = '<img src="{VALUE}" width="{WIDTH}" height="{HEIGHT}" />';
        $sut_fields_temps['su_pers_birth'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sut_fields_temps['su_pers_sex'] = '{VALUE}';
        $sut_fields_temps['su_pers_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_mob_phone'] = '{VALUE}';
        $sut_fields_temps['su_pers_zip'] = '{VALUE}';
        $sut_fields_temps['su_pers_adress'] = '{VALUE}';
        $sut_fields_temps['su_pers_addition'] = '{VALUE}';
        $sut_fields_temps['su_work_name'] = '{VALUE}';
        $sut_fields_temps['su_work_unit'] = '{VALUE}';
        $sut_fields_temps['su_work_position'] = '{VALUE}';
        $sut_fields_temps['su_work_office_number'] = '{VALUE}';
        $sut_fields_temps['su_work_phone'] = '{VALUE}';
        $sut_fields_temps['su_work_phone_inner'] = '{VALUE}';
        $sut_fields_temps['su_work_fax'] = '{VALUE}';
        $sut_fields_temps['su_work_email'] = '{VALUE}';
        $sut_fields_temps['su_work_addition'] = '{VALUE}';
        $sut_fields_temps['su_forum_nick'] = '{VALUE}';
        $sut_fields_temps['su_forum_text'] = '{VALUE}';
        $sut_fields_temps['su_forum_count_msg'] = '{VALUE}';
        $sut_fields_temps['su_forum_messages_link'] = '{VALUE}';

        $sut_categs_temps = array();

        $sut_messages = array();
        $sut_messages['need_auth'] = PL_SITE_USERS_DESIGN_DATA_EDIT_MESSAGES_NEED_AUTH;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sut_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_data_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sut_title, 'sut_title', '', 'style="width:550px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sut_lang');
    $fld->mSelOptions = array($sut_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes('pl_site_users', $sut_checked, 'sut_checked');

    fVoting_Design_Get($layout, $sut_votes_id, 'sut_votes_id');

    fComments_Design_Get($layout, $sut_comments_id, 'sut_comments_id');

    if ($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $fld = new sbLayoutPage(isset($sut_fields_temps['author_msg_page']) ? $sut_fields_temps['author_msg_page'] : '', 'sut_fields_temps[author_msg_page]', '', 'style="width: 450px;"');
        $fld->mHTML = '<div class="hint_div">'.PL_SITE_USERS_DESIGN_DATA_AUTHOR_MSG_PAGE_HINT.'</div>';
        $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_AUTHOR_MSG_PAGE, $fld);
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB2);

    $dop_tags = array('{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}');
    $dop_values = array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL);

    $tags = array('{VALUE}');
    $values = array(PL_SITE_USERS_DESIGN_EDIT_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_login'], 'sut_fields_temps[su_login]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_EMAIL}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_EMAIL));
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_email'], 'sut_fields_temps[su_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_ID}', '{SU_LOGIN}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2));
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date'], 'sut_fields_temps[su_reg_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);


    $fld = new sbLayoutTextarea($sut_fields_temps['su_change_date'], 'sut_fields_temps[su_change_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_CHANGE_DATE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date'], 'sut_fields_temps[su_last_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_LAST_DATE, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_status'], 'sut_fields_temps[su_status]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_active_date'], 'sut_fields_temps[su_active_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_ACTIVE, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_name'], 'sut_fields_temps[su_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_foto'], 'sut_fields_temps[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags);
    $fld->mValues = array_merge($values, array(SB_LAYOUT_VALUE_SIZE, SB_LAYOUT_VALUE_WIDTH, SB_LAYOUT_VALUE_HEIGHT), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_birth'], 'sut_fields_temps[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_sex'], 'sut_fields_temps[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_phone'], 'sut_fields_temps[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_mob_phone'], 'sut_fields_temps[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_zip'], 'sut_fields_temps[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_adress'], 'sut_fields_temps[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_pers_addition'], 'sut_fields_temps[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name'], 'sut_fields_temps[su_work_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit'], 'sut_fields_temps[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position'], 'sut_fields_temps[su_work_position]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number'], 'sut_fields_temps[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone'], 'sut_fields_temps[su_work_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner'], 'sut_fields_temps[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax'], 'sut_fields_temps[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email'], 'sut_fields_temps[su_work_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition'], 'sut_fields_temps[su_work_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION, $fld);

    $forum_tags = array();
    $forum_tags_values = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick'], 'sut_fields_temps[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text'], 'sut_fields_temps[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_count_msg'], 'sut_fields_temps[su_forum_count_msg]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_COUNT_AUTHOR_MSG, $fld);

        $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_messages_link'], 'sut_fields_temps[su_forum_messages_link]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_MESSAGES_LINK, $fld);

        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}', '{SU_FORUM_COUNT_MSG}', '{SU_FORUM_MESSAGES_LINK}');
        $forum_tags_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_EDIT_NICK, PL_SITE_USERS_EDIT_SIGNATURE, PL_SITE_USERS_EDIT_COUNT_AUTHOR_MSG, PL_SITE_USERS_EDIT_MESSAGES_LINK);
    }

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];

        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);

            if ($pd_fields)
            {
                if ($pd_fields[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', $dop_tags, $dop_values);
            }
        }
        else
        {
            $pd_fields = array();
        }

        if ($pd_categs != '')
        {
            $pd_categs = unserialize($pd_categs);

            if ($pd_categs)
            {
                if ($pd_categs[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB7.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sut_categs_temps, 'sut_', $dop_tags, $dop_values, true);
            }
        }
        else
        {
            $pd_categs = array();
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB3);

    $users_tags = array();
    $users_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $users_tags, $users_tags_values);

    $user_group_tags = array('-', '{SU_CAT_ID}', '{SU_CAT_TITLE}');
    $user_group_tags_values = array(PL_SITE_USERS_EDIT_CAT_GROUP_TAG, PL_SITE_USERS_EDIT_CAT_ID_TAG, PL_SITE_USERS_EDIT_CAT_TITLE_TAG);
    $layout->getPluginFieldsTags('pl_site_users', $user_group_tags, $user_group_tags_values, true);

    $fld = new sbLayoutTextarea($sut_form, 'sut_form', '', 'style="width:100%;height:350px;"');
    $fld->mTags = array_merge(
                    array('-', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_REG_DATE}', '{CHANGE_DATE}', '{SU_LAST_DATE}', '{SU_STATUS}', '{SU_ACTIVE_DATE}', '{SU_LOGOUT_LINK}', PL_SITE_USERS_TWITTER_LINK, PL_SITE_USERS_FACEBOOK_LINK, PL_SITE_USERS_ODK_LINK, PL_SITE_USERS_VK_LINK, PL_SITE_USERS_LJ_LINK),
                    array('-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}'),
                    array('-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $users_tags, $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
                    array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}'),
                    $user_group_tags);
    $fld->mValues = array_merge(
                    array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_EDIT_CHANGE_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, PL_SITE_USERS_DESIGN_DATA_GET_ACTIVE, PL_SITE_USERS_DESIGN_EDIT_LOGOUT_LINK, PL_SITE_USERS_TWITTER_LINK_TAG, PL_SITE_USERS_FACEBOOK_LINK_TAG, PL_SITE_USERS_ODK_LINK_TAG, PL_SITE_USERS_VK_LINK_TAG, PL_SITE_USERS_LJ_LINK_TAG),
                    array(PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION),
                    array(PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $users_tags_values, $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG, PL_SITE_USERS_DESIGN_FORM_TAG),
                    array(PL_SITE_USERS_DESIGN_DATA_EDIT_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_COUNT_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_LIST_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_FORM_COMMENTS_TAG),
                    $user_group_tags_values);
    
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_FORM, $fld);

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_MESSAGES_ERRORS, new sbLayoutTextarea($sut_messages['need_auth'], 'sut_messages[need_auth]', '', 'style="width:100%;height:60px;"'));

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_Data_Temp_Submit()
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_data_temp'))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sut_checked = array();
    $sut_lang = SB_CMS_LANG;
    $sut_fields_temps = array();
    $sut_categs_temps = array();
    $sut_messages = array();
    $sut_votes_id = 0;
    $sut_comments_id = 0;

    extract($_POST);

    if ($sut_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fSite_Users_Data_Temp_Edit();
        return;
    }

    $row = array();
    $row['sut_title'] = $sut_title;
    $row['sut_lang'] = $sut_lang;
    $row['sut_checked'] = implode(' ', $sut_checked);
    $row['sut_form'] = $sut_form;
    $row['sut_fields_temps'] = serialize($sut_fields_temps);
    $row['sut_categs_temps'] = serialize($sut_categs_temps);
    $row['sut_messages'] = serialize($sut_messages);
    $row['sut_votes_id'] = $sut_votes_id;
    $row['sut_comments_id'] = $sut_comments_id;

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sut_title FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps SET ?a WHERE sut_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_DESIGN_DATA_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_DATA_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fSite_Users_Data_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sut_id'] = intval($_GET['id']);

            $html_str = fSite_Users_Data_Temp_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fSite_Users_Data_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_DATA_EDIT_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Data_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_DATA_ADD_OK, $sut_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps WHERE sut_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_DESIGN_DATA_ADD_ERROR, $sut_title), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_DATA_ADD_SYSTEMLOG_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Data_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_Data_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_data" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_data" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}

/**
 * Функции управления макетами дизайна списка пользователей
 */
function fSite_Users_List_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sutl_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sutl_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_list" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_list" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';

    return $result;
}

function fSite_Users_List_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_site_users_temps_list', 'sutl_id', 'sutl_title', 'fSite_Users_List_Temp_Get', 'pl_site_users_list_temp', 'pl_site_users_list_temp');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_list_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sutl_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_list_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_list_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_list_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_list";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_list";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'usersList();', false);

    $elems->init();
}

function fSite_Users_List_Temp_Edit($htmlStr = '', $footerStr = '')
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_list_temp'))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sutl_title, sutl_lang, sutl_checked, sutl_count, sutl_top, sutl_categ_top, sutl_element,
                                   sutl_element, sutl_empty, sutl_delim, sutl_categ_bottom, sutl_bottom, sutl_pagelist_id, sutl_perpage,
                                   sutl_messages, sutl_fields_temps, sutl_categs_temps, sutl_votes_id, sutl_comments_id
                                   FROM sb_site_users_temps_list WHERE sutl_id=?d', $_GET['id']);

        if ($res)
        {
            list($sutl_title, $sutl_lang, $sutl_checked, $sutl_count, $sutl_top, $sutl_categ_top, $sutl_element,
                 $sutl_element, $sutl_empty, $sutl_delim, $sutl_categ_bottom, $sutl_bottom, $sutl_pagelist_id, $sutl_perpage,
                 $sutl_messages, $sutl_fields_temps, $sutl_categs_temps, $sutl_votes_id, $sutl_comments_id) = $res[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sutl_fields_temps != '')
            $sutl_fields_temps = unserialize($sutl_fields_temps);
        else
            $sutl_fields_temps = array();

        if ($sutl_categs_temps != '')
            $sutl_categs_temps = unserialize($sutl_categs_temps);
        else
            $sutl_categs_temps = array();

        if ($sutl_messages != '')
            $sutl_messages = unserialize($sutl_messages);
        else
            $sutl_messages = array();

        if ($sutl_checked != '')
            $sutl_checked = explode(' ', $sutl_checked);
        else
            $sutl_checked = array();

        if(!isset($sutl_fields_temps['su_forum_count_msg']))
            $sutl_fields_temps['su_forum_count_msg'] = '';
        if(!isset($sutl_fields_temps['su_forum_messages_link']))
            $sutl_fields_temps['su_forum_messages_link'] = '';
        if(!isset($sutl_fields_temps['su_change_date']))
            $sutl_fields_temps['su_change_date'] = '';
    }
    elseif (count($_POST) > 0)
    {
        $sutl_checked = array();
        $sutl_pagelist_id = -1;
        $sutl_votes_id = -1;
        $sutl_comments_id = -1;

        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sutl_title = $sutl_top = $sutl_categ_top = $sutl_element = $sutl_empty = $sutl_delim = $sutl_categ_bottom = $sutl_bottom = '';

        $sutl_pagelist_id = -1;
        $sutl_votes_id = -1;
        $sutl_comments_id = -1;
        $sutl_perpage = 10;
        $sutl_count = 1;
        $sutl_checked = array();
        $sutl_lang = SB_CMS_LANG;

        $sutl_fields_temps = array();
        $sutl_fields_temps['su_more'] = PL_SITE_USERS_DESIGN_EDIT_MORE_DEFAULT;
        $sutl_fields_temps['su_login'] = '{VALUE}';
        $sutl_fields_temps['su_email'] = '{VALUE}';
        $sutl_fields_temps['su_status'] = '{VALUE}';
        $sutl_fields_temps['su_reg_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sutl_fields_temps['su_change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sutl_fields_temps['su_last_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $sutl_fields_temps['su_active_date'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sutl_fields_temps['su_name'] = '{VALUE}';
        $sutl_fields_temps['su_pers_foto'] = '<img src="{VALUE}" width="{WIDTH}" height="{HEIGHT}" />';
        $sutl_fields_temps['su_pers_birth'] = '{DAY}.{MONTH}.{LONG_YEAR}';
        $sutl_fields_temps['su_pers_sex'] = '{VALUE}';
        $sutl_fields_temps['su_pers_phone'] = '{VALUE}';
        $sutl_fields_temps['su_pers_mob_phone'] = '{VALUE}';
        $sutl_fields_temps['su_pers_zip'] = '{VALUE}';
        $sutl_fields_temps['su_pers_adress'] = '{VALUE}';
        $sutl_fields_temps['su_pers_addition'] = '{VALUE}';
        $sutl_fields_temps['su_work_name'] = '{VALUE}';
        $sutl_fields_temps['su_work_unit'] = '{VALUE}';
        $sutl_fields_temps['su_work_position'] = '{VALUE}';
        $sutl_fields_temps['su_work_office_number'] = '{VALUE}';
        $sutl_fields_temps['su_work_phone'] = '{VALUE}';
        $sutl_fields_temps['su_work_phone_inner'] = '{VALUE}';
        $sutl_fields_temps['su_work_fax'] = '{VALUE}';
        $sutl_fields_temps['su_work_email'] = '{VALUE}';
        $sutl_fields_temps['su_work_addition'] = '{VALUE}';
        $sutl_fields_temps['su_forum_nick'] = '{VALUE}';
        $sutl_fields_temps['su_forum_text'] = '{VALUE}';
        $sutl_fields_temps['su_forum_count_msg'] = '{VALUE}';
        $sutl_fields_temps['su_forum_messages_link'] = '{VALUE}';

        $sutl_categs_temps = array();

        $sutl_messages = array();
        $sutl_messages['no_users'] = PL_SITE_USERS_DESIGN_LIST_EDIT_MESSAGES_NO_USERS;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sutl_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_list_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_TAB1);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sutl_title, 'sutl_title', '', 'style="width:550px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sutl_lang');
    //$fld->mSelOptions = array($sutl_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addPluginFieldsTempsCheckboxes('pl_site_users', $sutl_checked, 'sutl_checked');

    fVoting_Design_Get($layout, $sutl_votes_id, 'sutl_votes_id');

    fComments_Design_Get($layout, $sutl_comments_id, 'sutl_comments_id');

    fPager_Design_Get($layout, $sutl_pagelist_id, 'sutl_pagelist_id', $sutl_perpage, 'sutl_perpage');

    $layout->addField('', new sbLayoutDelim());

    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_NO_USERS, new sbLayoutTextarea($sutl_messages['no_users'], 'sutl_messages[no_users]', '', 'style="width:100%;height:60px;"'));

    $layout->addTab(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_DATA_EDIT_TAB2);

    $dop_tags = array('{SU_LINK}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_CAT_ID}', '{SU_CAT_TITLE}');
    $dop_values = array(PL_SITE_USERS_DESIGN_LIST_EDIT_LINK_TAG, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_EDIT_CAT_ID_TAG, PL_SITE_USERS_EDIT_CAT_TITLE_TAG);

    $tags = array('{VALUE}');
    $values = array(PL_SITE_USERS_DESIGN_EDIT_VALUE_TAG);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_login'], 'sutl_fields_temps[su_login]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_LINK}', '{SU_ID}', '{SU_EMAIL}', '{SU_CAT_ID}', '{SU_CAT_TITLE}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_DESIGN_LIST_EDIT_LINK_TAG, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_EDIT_CAT_ID_TAG, PL_SITE_USERS_EDIT_CAT_TITLE_TAG));
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_email'], 'sutl_fields_temps[su_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SU_LINK}', '{SU_ID}', '{SU_LOGIN}', '{SU_CAT_ID}', '{SU_CAT_TITLE}'));
    $fld->mValues = array_merge($values, array(PL_SITE_USERS_DESIGN_LIST_EDIT_LINK_TAG, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_CAT_ID_TAG, PL_SITE_USERS_EDIT_CAT_TITLE_TAG));
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_reg_date'], 'sutl_fields_temps[su_reg_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_change_date'], 'sutl_fields_temps[su_change_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_CHANGE_DATE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_last_date'], 'sutl_fields_temps[su_last_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_GET_LAST_DATE, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_status'], 'sutl_fields_temps[su_status]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_active_date'], 'sutl_fields_temps[su_active_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_ACTIVE, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB2.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_name'], 'sutl_fields_temps[su_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_foto'], 'sutl_fields_temps[su_pers_foto]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, array('{SIZE}', '{WIDTH}', '{HEIGHT}'), $dop_tags);
    $fld->mValues = array_merge($values, array(SB_LAYOUT_VALUE_SIZE, SB_LAYOUT_VALUE_WIDTH, SB_LAYOUT_VALUE_HEIGHT), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_AVATAR, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_birth'], 'sutl_fields_temps[su_pers_birth]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge(array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}'), $dop_tags);
    $fld->mValues = array_merge(array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG), $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_sex'], 'sutl_fields_temps[su_pers_sex]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_phone'], 'sutl_fields_temps[su_pers_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_PHONE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_mob_phone'], 'sutl_fields_temps[su_pers_mob_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_zip'], 'sutl_fields_temps[su_pers_zip]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_adress'], 'sutl_fields_temps[su_pers_adress]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_pers_addition'], 'sutl_fields_temps[su_pers_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB3.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_name'], 'sutl_fields_temps[su_work_name]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_unit'], 'sutl_fields_temps[su_work_unit]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_position'], 'sutl_fields_temps[su_work_position]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_office_number'], 'sutl_fields_temps[su_work_office_number]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_phone'], 'sutl_fields_temps[su_work_phone]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_phone_inner'], 'sutl_fields_temps[su_work_phone_inner]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_fax'], 'sutl_fields_temps[su_work_fax]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_email'], 'sutl_fields_temps[su_work_email]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    $fld = new sbLayoutTextarea($sutl_fields_temps['su_work_addition'], 'sutl_fields_temps[su_work_addition]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array_merge($tags, $dop_tags);
    $fld->mValues = array_merge($values, $dop_values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_ADDITION, $fld);

    $forum_tags = array();
    $forum_tags_values = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_forum'))
    {
        $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB4.'</div>';
        $layout->addField('', new sbLayoutHTML($html, true));
        $layout->addField('', new sbLayoutDelim());

        $fld = new sbLayoutTextarea($sutl_fields_temps['su_forum_nick'], 'sutl_fields_temps[su_forum_nick]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

        $fld = new sbLayoutTextarea($sutl_fields_temps['su_forum_text'], 'sutl_fields_temps[su_forum_text]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

        $fld = new sbLayoutTextarea($sutl_fields_temps['su_forum_count_msg'], 'sutl_fields_temps[su_forum_count_msg]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_COUNT_AUTHOR_MSG, $fld);

        $fld = new sbLayoutTextarea($sutl_fields_temps['su_forum_messages_link'], 'sutl_fields_temps[su_forum_messages_link]', '', 'style="width:100%;height:50px;"');
        $fld->mTags = array_merge($tags, $dop_tags);
        $fld->mValues = array_merge($values, $dop_values);
        $layout->addField(PL_SITE_USERS_EDIT_MESSAGES_LINK, $fld);

        $forum_tags = array('-', '{SU_FORUM_NICK}', '{SU_FORUM_TEXT}', '{SU_FORUM_COUNT_MSG}', '{SU_FORUM_MESSAGES_LINK}');
        $forum_tags_values = array(PL_SITE_USERS_TAB4, PL_SITE_USERS_EDIT_NICK, PL_SITE_USERS_EDIT_SIGNATURE, PL_SITE_USERS_EDIT_COUNT_AUTHOR_MSG, PL_SITE_USERS_EDIT_MESSAGES_LINK);
    }

    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields, $pd_categs) = $res[0];

        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);

            if ($pd_fields)
            {
                if ($pd_fields[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sutl_fields_temps, 'sutl_', $dop_tags, $dop_values);
            }
        }
        else
        {
            $pd_fields = array();
        }

        if ($pd_categs != '')
        {
            $pd_categs = unserialize($pd_categs);

            if ($pd_categs)
            {
                if ($pd_categs[0]['type'] != 'tab')
                {
                    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB7.'</div>';
                    $layout->addField('', new sbLayoutHTML($html, true));
                    $layout->addField('', new sbLayoutDelim());
                }

                $layout->addPluginFieldsTemps('pl_site_users', $sutl_categs_temps, 'sutl_', $dop_tags, $dop_values, true);
            }
        }
        else
        {
            $pd_categs = array();
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_LIST_EDIT_TAB3);
    $layout->addHeader(PL_SITE_USERS_DESIGN_LIST_EDIT_TAB3);

    $fld = new sbLayoutInput('text', $sutl_count, 'sutl_count', 'spin_sutl_count', 'style="width:100px;"');
    $fld->mMinValue = 1;

    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_COUNT, $fld);

    //Верх вывода
    $fld = new sbLayoutTextarea($sutl_top, 'sutl_top', '', 'style="width:100%;height:100px;"');
    $flds_tags = array('{NUM_LIST}', '{ALL_COUNT}', PL_SITE_USERS_DESIGN_EDIT_INPAGENUM_SELECT,
                        '<a href=\'{SORT_ID_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC.'</a>','<a href=\'{SORT_ID_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC.'</a>',
                        '<a href=\'{SORT_NAME_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_ASC.'</a>','<a href=\'{SORT_NAME_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_DESC.'</a>',
                        '<a href=\'{SORT_EMAIL_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_ASC.'</a>','<a href=\'{SORT_EMAIL_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_DESC.'</a>',
                        '<a href=\'{SORT_LOGIN_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_ASC.'</a>','<a href=\'{SORT_LOGIN_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_DESC.'</a>',
                        '<a href=\'{SORT_REG_DATE_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_ASC.'</a>','<a href=\'{SORT_REG_DATE_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_DESC.'</a>',
                        '<a href=\'{SORT_LAST_DATE_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_ASC.'</a>','<a href=\'{SORT_LAST_DATE_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_DESC.'</a>',
                        '<a href=\'{SORT_PERS_FOTO_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_ASC.'</a>','<a href=\'{SORT_PERS_FOTO_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_DESC.'</a>',
                        '<a href=\'{SORT_PERS_PHONE_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_ASC.'</a>','<a href=\'{SORT_PERS_PHONE_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_DESC.'</a>',
                        '<a href=\'{SORT_PERS_MOB_PHONE_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_ASC.'</a>','<a href=\'{SORT_PERS_MOB_PHONE_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_DESC.'</a>',
                        '<a href=\'{SORT_PERS_BIRTH_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_ASC.'</a>','<a href=\'{SORT_PERS_BIRTH_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_DESC.'</a>',
                        '<a href=\'{SORT_PERS_SEX_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_ASC.'</a>','<a href=\'{SORT_PERS_SEX_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_DESC.'</a>',
                        '<a href=\'{SORT_PERS_ZIP_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_ASC.'</a>','<a href=\'{SORT_PERS_ZIP_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_DESC.'</a>',
                        '<a href=\'{SORT_PERS_ADRESS_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_ASC.'</a>','<a href=\'{SORT_PERS_ADRESS_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_DESC.'</a>',
                        '<a href=\'{SORT_PERS_ADDITION_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_ASC.'</a>','<a href=\'{SORT_PERS_ADDITION_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_DESC.'</a>',
                        '<a href=\'{SORT_WORK_NAME_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_ASC.'</a>','<a href=\'{SORT_WORK_NAME_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_DESC.'</a>',
                        '<a href=\'{SORT_WORK_PHONE_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_ASC.'</a>','<a href=\'{SORT_WORK_PHONE_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_DESC.'</a>',
                        '<a href=\'{SORT_WORK_PHONE_INNER_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_ASC.'</a>','<a href=\'{SORT_WORK_PHONE_INNER_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_DESC.'</a>',
                        '<a href=\'{SORT_WORK_FAX_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_ASC.'</a>','<a href=\'{SORT_WORK_FAX_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_DESC.'</a>',
                        '<a href=\'{SORT_WORK_EMAIL_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_ASC.'</a>','<a href=\'{SORT_WORK_EMAIL_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_DESC.'</a>',
                        '<a href=\'{SORT_WORK_ADDITION_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_ASC.'</a>','<a href=\'{SORT_WORK_ADDITION_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_DESC.'</a>',
                        '<a href=\'{SORT_WORK_OFFICE_NUMBER_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_ASC.'</a>','<a href=\'{SORT_WORK_OFFICE_NUMBER_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_DESC.'</a>',
                        '<a href=\'{SORT_WORK_UNIT_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_ASC.'</a>','<a href=\'{SORT_WORK_UNIT_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_DESC.'</a>',
                        '<a href=\'{SORT_WORK_POSITION_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_ASC.'</a>','<a href=\'{SORT_WORK_POSITION_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_DESC.'</a>',
                        '<a href=\'{SORT_FORUM_NICK_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_ASC.'</a>','<a href=\'{SORT_FORUM_NICK_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_DESC.'</a>',
                        '<a href=\'{SORT_FORUM_TEXT_ASC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_ASC.'</a>','<a href=\'{SORT_FORUM_TEXT_DESC}\'>'.PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_DESC.'</a>');

    $flds_vals = array(PL_SITE_USERS_DESIGN_LIST_EDIT_PAGELIST_TAG, PL_SITE_USERS_DESIGN_LIST_EDIT_ALLNUM_TAG, PL_SITE_USERS_DESIGN_EDIT_INPAGENUM_TAG,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_DESC,
                            PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_DESC);

    $layout->getPluginFieldsTagsSort('su', $flds_tags, $flds_vals);
    $fld->mTags = $flds_tags;
    $fld->mValues = $flds_vals;
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_TOP, $fld);

    $layout->addField('', new sbLayoutDelim());

    $user_group_tags = array('-', '{SU_CAT_TITLE}', '{SU_CAT_COUNT}', '{SU_CAT_LEVEL}', '{SU_CAT_ID}');
    $user_group_tags_values = array(PL_SITE_USERS_EDIT_CAT_GROUP_TAG, PL_SITE_USERS_EDIT_CAT_TITLE_TAG, PL_SITE_USERS_EDIT_CAT_COUNT_TAG, PL_SITE_USERS_EDIT_CAT_LEVEL_TAG, PL_SITE_USERS_EDIT_CAT_ID_TAG);
    $layout->getPluginFieldsTags('pl_site_users', $user_group_tags, $user_group_tags_values, true);

    $fld = new sbLayoutTextarea($sutl_categ_top, 'sutl_categ_top', '', 'style="width:100%;height:100px;"');
    $fld->mTags = $user_group_tags;
    $fld->mValues = $user_group_tags_values;
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_CAT_TOP, $fld);

    $users_tags = array();
    $users_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $users_tags, $users_tags_values);

    $fld = new sbLayoutTextarea($sutl_element, 'sutl_element', '', 'style="width:100%;height:350px;"');
    $fld->mTags = array_merge(
                    array('-', '{SU_NUMBER}', '{SU_ID}', '{SU_LOGIN}', '{SU_EMAIL}', '{SU_LINK}', '{SU_REG_DATE}', '{CHANGE_DATE}', '{SU_LAST_DATE}', '{SU_STATUS}', '{SU_ACTIVE_DATE}'),
                    array('-', '{SU_NAME}', '{SU_PERS_FOTO}', '{SU_PERS_BIRTH}', '{SU_PERS_SEX}', '{SU_PERS_PHONE}', '{SU_PERS_MOB_PHONE}', '{SU_PERS_ZIP}', '{SU_PERS_ADRESS}', '{SU_PERS_ADDITION}'),
                    array('-', '{SU_WORK_NAME}', '{SU_WORK_UNIT}', '{SU_WORK_POSITION}', '{SU_WORK_OFFICE_NUMBER}', '{SU_WORK_PHONE}', '{SU_WORK_PHONE_INNER}', '{SU_WORK_FAX}', '{SU_WORK_EMAIL}', '{SU_WORK_ADDITION}'),
                    $users_tags, $forum_tags,
                    array('-', '{RATING}', '{VOTES_COUNT}', '{VOTES_SUM}', '{VOTES_FORM}'),
                    array('-', '{COUNT_COMMENTS}', '{LIST_COMMENTS}', '{FORM_COMMENTS}'),
                    $user_group_tags);
    $fld->mValues = array_merge(
                    array(PL_SITE_USERS_TAB1, PL_SITE_USERS_EDIT_NUMBER, PL_SITE_USERS_EDIT_ID, PL_SITE_USERS_EDIT_LOGIN2, PL_SITE_USERS_EDIT_EMAIL, PL_SITE_USERS_DESIGN_LIST_EDIT_LINK_TAG, PL_SITE_USERS_GET_REG_DATE, PL_SITE_USERS_EDIT_CHANGE_DATE, PL_SITE_USERS_GET_LAST_DATE, PL_SITE_USERS_DESIGN_DATA_EDIT_STATUS, PL_SITE_USERS_DESIGN_DATA_GET_ACTIVE),
                    array(PL_SITE_USERS_TAB2, PL_SITE_USERS_EDIT_FIO, PL_SITE_USERS_EDIT_AVATAR, PL_SITE_USERS_EDIT_BIRTH, PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_EDIT_PHONE, PL_SITE_USERS_EDIT_MOBILE, PL_SITE_USERS_EDIT_ZIP, PL_SITE_USERS_EDIT_ADRESS, PL_SITE_USERS_EDIT_ADDITION),
                    array(PL_SITE_USERS_TAB3, PL_SITE_USERS_EDIT_WORK_NAME, PL_SITE_USERS_EDIT_OTDEL, PL_SITE_USERS_EDIT_DOLJ, PL_SITE_USERS_EDIT_WORK_OFFICE, PL_SITE_USERS_EDIT_WORK_PHONE, PL_SITE_USERS_EDIT_WORK_PHONE_INNER, PL_SITE_USERS_EDIT_WORK_FAX, PL_SITE_USERS_EDIT_WORK_EMAIL, PL_SITE_USERS_EDIT_WORK_ADDITION),
                    $users_tags_values, $forum_tags_values,
                    array(PL_SITE_USERS_DESIGN_RATING_TITLE_TAG, PL_SITE_USERS_DESIGN_RATING_USER_TAG, PL_SITE_USERS_DESIGN_VOTES_COUNT_TAG, PL_SITE_USERS_DESIGN_VOTES_SUM_TAG, PL_SITE_USERS_DESIGN_FORM_TAG),
                    array(PL_SITE_USERS_DESIGN_DATA_EDIT_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_COUNT_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_LIST_COMMENTS_TAG, PL_SITE_USERS_DESIGN_DATA_EDIT_FORM_COMMENTS_TAG),
                    $user_group_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_ELEMENT, $fld);

    $fld = new sbLayoutTextarea($sutl_empty, 'sutl_empty', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_EMPTY, $fld);

    $fld = new sbLayoutTextarea($sutl_delim, 'sutl_delim', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_DELIM, $fld);

    $fld = new sbLayoutTextarea($sutl_categ_bottom, 'sutl_categ_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = $user_group_tags;
    $fld->mValues = $user_group_tags_values;
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_CAT_BOTTOM, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sutl_bottom, 'sutl_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = $flds_tags;
    $fld->mValues = $flds_vals;
    $layout->addField(PL_SITE_USERS_DESIGN_LIST_EDIT_BOTTOM, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fSite_Users_List_Temp_Submit()
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_list_temp'))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sutl_pagelist_id = 0;
    $sutl_votes_id = 0;
    $sutl_comments_id = 0;
    $sutl_checked = array();
    $sutl_lang = SB_CMS_LANG;
    $sutl_fields_temps = array();
    $sutl_categs_temps = array();
    $sutl_messages = array();

    extract($_POST);

    if ($sutl_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fSite_Users_List_Temp_Edit();
        return;
    }

    $row = array();
    $row['sutl_title'] = $sutl_title;
    $row['sutl_lang'] = $sutl_lang;
    $row['sutl_checked'] = implode(' ', $sutl_checked);
    $row['sutl_count'] = $sutl_count;
    $row['sutl_top'] = $sutl_top;
    $row['sutl_categ_top'] = $sutl_categ_top;
    $row['sutl_element'] = $sutl_element;
    $row['sutl_empty'] = $sutl_empty;
    $row['sutl_delim'] = $sutl_delim;
    $row['sutl_categ_bottom'] = $sutl_categ_bottom;
    $row['sutl_bottom'] = $sutl_bottom;
    $row['sutl_pagelist_id'] = $sutl_pagelist_id;
    $row['sutl_perpage'] = $sutl_perpage;
    $row['sutl_messages'] = serialize($sutl_messages);
    $row['sutl_fields_temps'] = serialize($sutl_fields_temps);
    $row['sutl_categs_temps'] = serialize($sutl_categs_temps);
    $row['sutl_votes_id'] = $sutl_votes_id;
    $row['sutl_comments_id'] = $sutl_comments_id;

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sutl_title FROM sb_site_users_temps_list WHERE sutl_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps_list SET ?a WHERE sutl_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_DESIGN_LIST_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_site_users_temps_list', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_LIST_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fSite_Users_List_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sutl_id'] = intval($_GET['id']);

            $html_str = fSite_Users_List_Temp_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fSite_Users_List_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_LIST_EDIT_SYSTEMLOG_ERROR, $sutl_title), SB_MSG_WARNING);

            fSite_Users_List_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps_list SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_site_users_temps_list', $id);
                sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_LIST_ADD_OK, $sutl_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps_list WHERE sutl_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_DESIGN_LIST_ADD_ERROR, $sutl_title), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_DESIGN_LIST_ADD_SYSTEMLOG_ERROR, $sutl_title), SB_MSG_WARNING);

            fSite_Users_List_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_List_Temp_Delete()
{
    $id = intval($_GET['id']);

    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_list" AND pages.p_id=elems.e_p_id LIMIT 1');

    $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_list" AND temps.t_id=elems.e_p_id LIMIT 1');

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления макетами дизайна вывода разделов
 */
function fSite_Users_Categ_Temp_Get($args)
{
    return fCategs_Design_Get($args, 'pl_site_users_categs', 'pl_site_users');
}

function fSite_Users_Categ_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_list', 'ctl_id', 'ctl_title', 'fSite_Users_Categ_Temp_Get', 'pl_site_users_categ_temp', 'pl_site_users_categs');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'ctl_title');

    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_categ_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_categ_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_categ_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function usersList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_H_PLUGIN_NAME, 'usersList();', false);

    $elems->init();
}

function fSite_Users_Categ_Temp_Edit()
{
    fCategs_Design_Edit('pl_site_users_categs', 'pl_site_users', 'pl_site_users_categ_temp_submit');
}

function fSite_Users_Categ_Temp_Submit()
{
    fCategs_Design_Edit_Submit('pl_site_users_categs', 'pl_site_users', 'pl_site_users_categ_temp_submit', 'pl_site_users_categs', 'pl_site_users');
}

function fSite_Users_Categ_Temp_Delete()
{
    fCategs_Design_Delete('pl_site_users_categs', 'pl_site_users');
}


/**
 * Функции управления макетами дизайна формы фильтра
 */
function fSite_Users_Filter_Temp_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['sut_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if(is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['sut_id']);
    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_filter" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_filter" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');
    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';
        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';
    return $result;
}

function fSite_Users_Filter_Temp()
{
    require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
    $elems = new sbElements('sb_site_users_temps', 'sut_id', 'sut_title', 'fSite_Users_Filter_Temp_Get', 'pl_site_users_filter_temp', 'pl_site_users_filter');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->addField('sut_title');      // название макета дизайна
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_site_users_filter_32.png';

    $elems->addSorting(PL_SITE_USERS_DESIGN_EDIT_SORT_BY_TITLE, 'sut_title');
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_site_users_filter_temp_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_site_users_filter_temp_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_site_users_filter_temp_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;

    $elems->mElemsJavascriptStr .= '
        function linkPages(e)
        {
            if (!sbSelEl)
            {
                var el = sbEventTarget(e);

                while (el.parentNode && !el.getAttribute("true_id"))
                    el = el.parentNode;

                var el_id = el.getAttribute("el_id");
            }
            else
                var el_id = sbSelEl.getAttribute("el_id");

            strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_site_users_filter";
            strAttr = "resizable=1,width=800,height=600";
            sbShowModalDialog(strPage, strAttr, null, window);
        }
        function linkTemps(e)
        {
            if (!sbSelEl)
            {
                var el = sbEventTarget(e);

                while (el.parentNode && !el.getAttribute("true_id"))
                    el = el.parentNode;

                var el_id = el.getAttribute("el_id");
            }
            else
                var el_id = sbSelEl.getAttribute("el_id");

            strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_site_users_filter";
            strAttr = "resizable=1,width=800,height=600";
            sbShowModalDialog(strPage, strAttr, null, window);
        }

        function suList()
        {
            window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_site_users_init";
        }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_SITE_USERS_USERS_MENU, 'suList();', false);

    $elems->init();
}

function fSite_Users_Filter_Temp_Edit($htmlStr = '', $footerStr = '')
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_filter'))
        return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT sut_title, sut_lang, sut_form, sut_fields_temps
                                    FROM sb_site_users_temps  WHERE sut_id=?d', $_GET['id']);
        if($result)
        {
            list($sut_title, $sut_lang, $sut_form, $sut_fields_temps) = $result[0];
        }
        else
        {
            sb_show_message(PL_SITE_USERS_FILTER_TEMP_EDIT_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($sut_fields_temps != '')
            $sut_fields_temps = unserialize($sut_fields_temps);
        else
            $sut_fields_temps = array();
         if(!isset($sut_fields_temps['su_sort_select']))
            $sut_fields_temps['su_sort_select'] = PL_SITE_USERS_FILTER_FORM_EDIT_SORT_SELECT_FIELD;

    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $sut_lang = SB_CMS_LANG;
        $sut_title = $sut_form = '';

        $sut_fields_temps = array();
        $sut_fields_temps['date_temps'] = '{DAY}.{MONTH}.{LONG_YEAR}';

        $sut_fields_temps['su_id'] = PL_SITE_USERS_FILTER_FORM_ID_FIELD;
        $sut_fields_temps['su_id_lo'] = PL_SITE_USERS_FILTER_FORM_ID_LO_FIELD;
        $sut_fields_temps['su_id_hi'] = PL_SITE_USERS_FILTER_FORM_ID_HI_FIELD;
        $sut_fields_temps['su_login'] = PL_SITE_USERS_FILTER_FORM_LOGIN_FIELD;
        $sut_fields_temps['su_name'] = PL_SITE_USERS_FILTER_FORM_NAME_FIELD;
        $sut_fields_temps['su_email'] = PL_SITE_USERS_FILTER_FORM_EMAIL_FIELD;
        $sut_fields_temps['su_reg_date'] = PL_SITE_USERS_FILTER_FORM_REG_DATE_FIELD;
        $sut_fields_temps['su_reg_date_lo'] = PL_SITE_USERS_FILTER_FORM_REG_DATE_LO_FIELD;
        $sut_fields_temps['su_reg_date_hi'] = PL_SITE_USERS_FILTER_FORM_REG_DATE_HI_FIELD;
        $sut_fields_temps['su_last_date'] = PL_SITE_USERS_FILTER_FORM_LAST_DATE_FIELD;
        $sut_fields_temps['su_last_date_lo'] = PL_SITE_USERS_FILTER_FORM_LAST_DATE_LO_FIELD;
        $sut_fields_temps['su_last_date_hi'] = PL_SITE_USERS_FILTER_FORM_LAST_DATE_HI_FIELD;
        $sut_fields_temps['su_active_date'] = PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_FIELD;
        $sut_fields_temps['su_active_date_lo'] = PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_LO_FIELD;
        $sut_fields_temps['su_active_date_hi'] = PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_HI_FIELD;
        $sut_fields_temps['su_phone'] = PL_SITE_USERS_FILTER_FORM_PHONE_FIELD;
        $sut_fields_temps['su_mob_phone'] = PL_SITE_USERS_FILTER_FORM_PERS_MOB_FIELD;
        $sut_fields_temps['su_birth'] = PL_SITE_USERS_FILTER_FORM_PERS_BIRTH_FIELD;
        $sut_fields_temps['su_zip'] = PL_SITE_USERS_FILTER_FORM_PERS_ZIP_FIELD;
        $sut_fields_temps['su_adress'] = PL_SITE_USERS_FILTER_FORM_PERS_ADRESS_FIELD;
        $sut_fields_temps['su_addition'] = PL_SITE_USERS_FILTER_FORM_PERS_ADDITION_FIELD;
        $sut_fields_temps['su_work_name'] = PL_SITE_USERS_FILTER_FORM_WORK_NAME_FIELD;
        $sut_fields_temps['su_work_phone'] = PL_SITE_USERS_FILTER_FORM_WORK_PHONE_FIELD;
        $sut_fields_temps['su_work_phone_inner'] = PL_SITE_USERS_FILTER_FORM_WORK_PHONE_INNER_FIELD;
        $sut_fields_temps['su_work_fax'] = PL_SITE_USERS_FILTER_FORM_WORK_FAX_FIELD;
        $sut_fields_temps['su_work_email'] = PL_SITE_USERS_FILTER_FORM_WORK_EMAIL_FIELD;
        $sut_fields_temps['su_work_addition'] = PL_SITE_USERS_FILTER_FORM_WORK_ADDITION_FIELD;
        $sut_fields_temps['su_status'] = PL_SITE_USERS_FILTER_FORM_STATUS_FIELD;
        $sut_fields_temps['su_sex'] = PL_SITE_USERS_FILTER_FORM_SEX_FIELD;
        $sut_fields_temps['su_work_office_number'] = PL_SITE_USERS_FILTER_FORM_OFFICE_NUMBER_FIELD;
        $sut_fields_temps['su_work_unit'] = PL_SITE_USERS_FILTER_FORM_WORK_UNIT_FIELD;
        $sut_fields_temps['su_work_position'] = PL_SITE_USERS_FILTER_FORM_WORK_POSITION_FIELD;
        $sut_fields_temps['su_forum_nick'] = PL_SITE_USERS_FILTER_FORM_FORUM_NICK_FIELD;
        $sut_fields_temps['su_forum_text'] = PL_SITE_USERS_FILTER_FORM_FORUM_TEXT_FIELD;
        $sut_fields_temps['su_status_option'] = PL_SITE_USERS_FILTER_FORM_STATUS_OPTION_FIELD;
        $sut_fields_temps['su_sex_option'] = PL_SITE_USERS_FILTER_FORM_STATUS_OPTION_FIELD;
        $sut_fields_temps['su_sort_select'] = PL_SITE_USERS_FILTER_FORM_EDIT_SORT_SELECT_FIELD;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("sut_title");
                if (el_title.value == "")
                {
                     alert("'.PL_SITE_USERS_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';
    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_filter_temp_submit'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');

    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '160';

    $layout->addTab(PL_SITE_USERS_TAB1);
    $layout->addHeader(PL_SITE_USERS_TAB1);

    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $sut_title, 'sut_title', '', 'style="width:97%;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($sut_fields_temps['date_temps'], 'sut_fields_temps[date_temps]', '', 'style="width:100%; height:70px;"');
    $fld->mTags = array('{DAY}', '{MONTH}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_MONTH_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_SITE_USERS_DESIGN_REG_EDIT_DATE_FORMAT, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'sut_lang');
    $fld->mSelOptions = array($sut_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_LANG, $fld);

    $layout->addTab(PL_SITE_USERS_DESIGN_REG_EDIT_TAB2);
    $layout->addHeader(PL_SITE_USERS_DESIGN_REG_EDIT_TAB2);

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB1.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

    $layout->addField('', new sbLayoutDelim());

    $tags = array('{VALUE}');
    $values = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_VALUE_TAG);

    // идентификаторов пользователя сайта (для полного совпадения)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_id'], 'sut_fields_temps[su_id]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ID_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_ID_FIELD_LABEL, $fld);

    // Идетификатор пользователя сайта (Начало интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_id_lo'], 'sut_fields_temps[su_id_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ID_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_ID_LO_FIELD_LABEL, $fld);

    // Конец интервала идентификаторов новостей
    $fld = new sbLayoutTextarea($sut_fields_temps['su_id_hi'], 'sut_fields_temps[su_id_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ID_HI_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_ID_HI_FIELD_LABEL, $fld);

    // Логин
    $fld = new sbLayoutTextarea($sut_fields_temps['su_login'], 'sut_fields_temps[su_login]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_LOGIN_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_LOGIN2, $fld);

    // Ф.И.О пользователя
    $fld = new sbLayoutTextarea($sut_fields_temps['su_name'], 'sut_fields_temps[su_name]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_NAME_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_FIO, $fld);

    // Email пользователя
    $fld = new sbLayoutTextarea($sut_fields_temps['su_email'], 'sut_fields_temps[su_email]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_EMAIL_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_EMAIL, $fld);

    //  Дата регистрации полное совподение
    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date'], 'sut_fields_temps[su_reg_date]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_REG_DATE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_GET_REG_DATE, $fld);

    //  Дата регистрации (начало интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date_lo'], 'sut_fields_temps[su_reg_date_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_REG_DATE_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_REG_DATE_LO_FIELD_LABEL, $fld);

    //  Дата регистрации (конец интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_reg_date_hi'], 'sut_fields_temps[su_reg_date_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_REG_DATE_HI_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_REG_DATE_HI_FIELD_LABEL, $fld);

    // Дата последней активности
    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date'], 'sut_fields_temps[su_last_date]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_LAST_DATE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_GET_LAST_DATE, $fld);

    // Дата последней активности (начало интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date_lo'], 'sut_fields_temps[su_last_date_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_LAST_DATE_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_LAST_DATE_LO_FIELD_LABEL, $fld);

    // Дата последней активности (конец интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_last_date_hi'], 'sut_fields_temps[su_last_date_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_LAST_DATE_HI_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_LAST_DATE_HI_FIELD_LABEL, $fld);

    // Дата до которой активен пользователь (польное соответствие)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_active_date'], 'sut_fields_temps[su_active_date]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_DESIGN_DATA_EDIT_ACTIVE, $fld);

    // Дата до которой активен пользователь (начало интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_active_date_lo'], 'sut_fields_temps[su_active_date_lo]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_LO_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_ACTIVE_DATE_LO_FIELD_LABEL, $fld);

    // Дата до которой активен пользователь (конец интервала)
    $fld = new sbLayoutTextarea($sut_fields_temps['su_active_date_hi'], 'sut_fields_temps[su_active_date_hi]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_ACTIVE_DATE_HI_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_FILTER_FORM_EDIT_ACTIVE_DATE_HI_FIELD_LABEL, $fld);

    //  Телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_phone'], 'sut_fields_temps[su_phone]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PHONE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    //  Мобильный телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_mob_phone'], 'sut_fields_temps[su_mob_phone]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PERS_MOB_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_MOBILE, $fld);

    //  Дата рождения
    $fld = new sbLayoutTextarea($sut_fields_temps['su_birth'], 'sut_fields_temps[su_birth]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PERS_BIRTH_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_BIRTH, $fld);

    //  Почтовый индекс
    $fld = new sbLayoutTextarea($sut_fields_temps['su_zip'], 'sut_fields_temps[su_zip]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PERS_ZIP_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ZIP, $fld);

    //  Почтовый адрес
    $fld = new sbLayoutTextarea($sut_fields_temps['su_adress'], 'sut_fields_temps[su_adress]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PERS_ADRESS_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ADRESS, $fld);

    //  Дополнительная информация
    $fld = new sbLayoutTextarea($sut_fields_temps['su_addition'], 'sut_fields_temps[su_addition]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_PERS_ADDITION_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    //  Название кампании
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_name'], 'sut_fields_temps[su_work_name]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_NAME_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_NAME, $fld);

    // Телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone'], 'sut_fields_temps[su_work_phone]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_PHONE_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE, $fld);

    // Внутренний телефон
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_phone_inner'], 'sut_fields_temps[su_work_phone_inner]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_PHONE_INNER_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_PHONE_INNER, $fld);

    // Факс
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_fax'], 'sut_fields_temps[su_work_fax]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_FAX_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_FAX, $fld);

    // Рабочий email
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_email'], 'sut_fields_temps[su_work_email]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_EMAIL_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_EMAIL, $fld);

    // Дополнительная информация
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_addition'], 'sut_fields_temps[su_work_addition]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_ADDITION_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_ADDITION, $fld);

    //  Статус
    $fld = new sbLayoutTextarea($sut_fields_temps['su_status'], 'sut_fields_temps[su_status]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_FILTER_FORM_STATUS_FIELD, '{OPTIONS}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
    $layout->addField(PL_SITE_USERS_EDIT_STATUS, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_status_option'], 'sut_fields_temps[su_status_option]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_STATUS_OPTION_FIELD), array('{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}'));
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), array(PL_SITE_USERS_EDIT_STATUS, PL_SITE_USERS_FILTER_FORM_STATUS_KOD_VALUE_TAG,
                                            PL_SITE_USERS_FILTER_FORM_SEL_OPTION_TAG));
    $layout->addField('', $fld);

    // Пол
    $fld = new sbLayoutTextarea($sut_fields_temps['su_sex'], 'sut_fields_temps[su_sex]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array(PL_SITE_USERS_FILTER_FORM_SEX_FIELD, '{OPTIONS}');
    $fld->mValues = array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG, SB_LAYOUT_PLUGIN_INPUT_FIELDS_OPT_TAG);
    $layout->addField(PL_SITE_USERS_EDIT_SEX, $fld);

    $fld = new sbLayoutTextarea($sut_fields_temps['su_sex_option'], 'sut_fields_temps[su_sex_option]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_STATUS_OPTION_FIELD), array('{OPT_TEXT}', '{OPT_VALUE}', '{OPT_SELECTED}'));
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), array(PL_SITE_USERS_EDIT_SEX, PL_SITE_USERS_FILTER_FORM_SEX_KOD_VALUE_TAG,
                                        PL_SITE_USERS_FILTER_FORM_SEL_OPTION_TAG));
    $layout->addField('', $fld);

    // Номер офиса
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_office_number'], 'sut_fields_temps[su_work_office_number]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_OFFICE_NUMBER_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_WORK_OFFICE, $fld);

    // Отдел / Подразделение
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_unit'], 'sut_fields_temps[su_work_unit]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_UNIT_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_OTDEL, $fld);

    // Должность
    $fld = new sbLayoutTextarea($sut_fields_temps['su_work_position'], 'sut_fields_temps[su_work_position]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_WORK_POSITION_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_DOLJ, $fld);

    // Ник
    $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_nick'], 'sut_fields_temps[su_forum_nick]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_FORUM_NICK_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_NICK, $fld);

    // Подпись
    $fld = new sbLayoutTextarea($sut_fields_temps['su_forum_text'], 'sut_fields_temps[su_forum_text]', '', 'style="width:100%; height:50px;"');
    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_FORUM_TEXT_FIELD), $tags);
    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG), $values);
    $layout->addField(PL_SITE_USERS_EDIT_SIGNATURE, $fld);

    //  Поля сортировки
    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_DESIGN_FORM_SORT_FIELDS.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));
    $layout->addField('', new sbLayoutDelim());

    // Макет полей сортировки
    $fld = new sbLayoutTextarea($sut_fields_temps['su_sort_select'], 'sut_fields_temps[su_sort_select]', '', 'style="width:100%;height:50px;"');
        //Вытаскиваю пользовательские поля
    $user_flds_tags = array();
    $user_flds_vals = array();
    $user_flds = $layout->getPluginFieldsTagsSort('su', $user_flds_tags, $user_flds_vals, 'option');

    $fld->mTags = array_merge(array(PL_SITE_USERS_FILTER_FORM_EDIT_SORT_SELECT_FIELD,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_ID_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_ID_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_NAME_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_NAME_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_EMAIL_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_EMAIL_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_LOGIN_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_LOGIN_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_REG_DATE_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_REG_DATE_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_LAST_DATE_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_LAST_DATE_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_FOTO_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_FOTO_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_PHONE_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_PHONE_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_MOB_PHONE_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_MOB_PHONE_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_BIRTH_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_BIRTH_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_SEX_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_SEX_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ZIP_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ZIP_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ADRESS_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ADRESS_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ADDITION_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_PERS_ADDITION_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_NAME_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_NAME_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_PHONE_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_PHONE_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_PHONE_INNER_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_PHONE_INNER_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_FAX_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_FAX_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_EMAIL_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_EMAIL_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_ADDITION_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_ADDITION_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_OFFICE_NUMBER_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_OFFICE_NUMBER_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_UNIT_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_UNIT_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_POSITION_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_WORK_POSITION_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FORUM_NICK_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FORUM_NICK_FIELD_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FORUM_TEXT_FIELD_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FORUM_TEXT_FIELD_DESC), $user_flds_tags);

    $fld->mValues = array_merge(array(SB_LAYOUT_PLUGIN_INPUT_FIELDS_HTML_TAG,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_ID_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_NAME_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_EMAIL_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LOGIN_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_REG_DATE_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_LAST_DATE_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_FOTO_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_PHONE_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_MOB_PHONE_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_BIRTH_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_SEX_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ZIP_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADRESS_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_PERS_ADDITION_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_NAME_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_PHONE_INNER_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_FAX_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_EMAIL_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_ADDITION_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_OFFICE_NUMBER_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_UNIT_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_WORK_POSITION_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_NICK_DESC,
                                        PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_ASC, PL_SITE_USERS_FILTER_FORM_EDIT_SORT_FIELDS_FORUM_TEXT_DESC),$user_flds_vals);
    $layout->addField(PL_SITE_USERS_DESIGN_FORM_SORT_SELECT_FIELDS, $fld);

    $res = sql_query('SELECT pd_fields FROM sb_plugins_data WHERE pd_plugin_ident="pl_site_users"');
    if ($res)
    {
        list($pd_fields) = $res[0];
        if ($pd_fields != '')
        {
            $pd_fields = unserialize($pd_fields);
            if(isset($pd_fields[0]['type']) && $pd_fields[0]['type'] != 'tab')
            {
                $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_SITE_USERS_TAB5.'</div>';
                $layout->addField('', new sbLayoutHTML($html, true));
                $layout->addField('', new sbLayoutDelim());
            }

            // Пользовательские поля
            $layout->addPluginInputFieldsTemps('pl_site_users', $sut_fields_temps, 'sut_', '', array(), array(), false, false, 'su_f', '', true);
        }
    }

    $layout->addTab(PL_SITE_USERS_DESIGN_EDIT_FORM);
    $layout->addHeader(PL_SITE_USERS_DESIGN_EDIT_FORM);

    $user_tags = array();
    $user_tags_values = array();
    $layout->getPluginFieldsTags('pl_site_users', $user_tags, $user_tags_values, false, true, false, true);

    $fld = new sbLayoutTextarea($sut_form, 'sut_form', '', 'style="width:100%; height:250px;"');
    $fld->mTags = array_merge(array('-', PL_SITE_USERS_FILTER_FORM_EDIT_HTML_FORM_TAG,
                                        '{SU_ID}',
                                        '{SU_ID_LO}',
                                        '{SU_ID_HI}',
                                        '{SU_LOGIN}',
                                        '{SU_EMAIL}',
                                        '{SU_REG_DATE}',
                                        '{SU_REG_DATE_LO}',
                                        '{SU_REG_DATE_HI}',
                                        '{SU_LAST_DATE}',
                                        '{SU_LAST_DATE_LO}',
                                        '{SU_LAST_DATE_HI}',
                                        '{SU_STATUS}',
                                        '{SU_ACTIVE_DATE}',
                                        '{SU_ACTIVE_DATE_LO}',
                                        '{SU_ACTIVE_DATE_HI}',
                                        '{SORT_SELECT}',
                                        '-',
                                        '{SU_NAME}',
                                        '{SU_PERS_BIRTH}',
                                        '{SU_PERS_SEX}',
                                        '{SU_PERS_PHONE}',
                                        '{SU_PERS_MOB_PHONE}',
                                        '{SU_PERS_ZIP}',
                                        '{SU_PERS_ADRESS}',
                                        '{SU_PERS_ADDITION}',
                                        '-',
                                        '{SU_WORK_NAME}',
                                        '{SU_WORK_UNIT}',
                                        '{SU_WORK_POSITION}',
                                        '{SU_WORK_OFFICE_NUMBER}',
                                        '{SU_WORK_PHONE}',
                                        '{SU_WORK_PHONE_INNER}',
                                        '{SU_WORK_FAX}',
                                        '{SU_WORK_EMAIL}',
                                        '{SU_WORK_ADDITION}',
                                        '-',
                                        '{SU_FORUM_NICK}',
                                        '{SU_FORUM_TEXT}'), $user_tags);
    $fld->mValues = array_merge(array(PL_SITE_USERS_TAB1,
                                PL_SITE_USERS_DESIGN_EDIT_FORM_TAG,
                                PL_SITE_USERS_FILTER_FORM_ID_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_ID_LO_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_ID_HI_FIELD_TAG,
                                PL_SITE_USERS_DESIGN_EDIT_LOGIN_TAG,
                                PL_SITE_USERS_DESIGN_EDIT_EMAIL_TAG,
                                PL_SITE_USERS_FILTER_FORM_REG_DATE_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_REG_DATE_LO_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_REG_DATE_HI_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_LAST_DATE_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_LAST_DATE_LO_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_LAST_DATE_HI_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_STATUS_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_ACTIVE_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_ACTIVE_LO_FIELD_TAG,
                                PL_SITE_USERS_FILTER_FORM_ACTIVE_HI_FIELD_TAG,
                                PL_SITE_USERS_DESIGN_FORM_SORT_SELECT_TAG_VALUE,
                                PL_SITE_USERS_TAB2,
                                PL_SITE_USERS_DESIGN_REG_EDIT_NAME_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_BIRTH_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_SEX_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_PHONE_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_MOB_PHONE_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ZIP_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADRESS_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_PERS_ADDITION_TAG,
                                PL_SITE_USERS_TAB3,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_NAME_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_UNIT_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_POSITION_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_OFFICE_NUMBER_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_PHONE_INNER_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_FAX_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_EMAIL_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_WORK_ADDITION_TAG,
                                PL_SITE_USERS_TAB4,
                                PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_NICK_TAG,
                                PL_SITE_USERS_DESIGN_REG_EDIT_FORUM_TEXT_TAG), $user_tags_values);
    $layout->addField(PL_SITE_USERS_DESIGN_EDIT_FORM , $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'design_edit') ? '' : 'disabled="disabled"'));

    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fSite_Users_Filter_Temp_Submit()
{
    // проверка прав доступа
    if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_site_users_filter'))
        return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $sut_title = $sut_form = '';
    $sut_lang = SB_CMS_LANG;
    $sut_fields_temps = array();

    extract($_POST);

    if ($sut_title == '')
    {
        sb_show_message(PL_SITE_USERS_DESIGN_NO_TITLE_MSG, false, 'warning');
        fSite_Users_Filter_Temp_Edit();
        return;
    }

    $row = array();
    $row['sut_title'] = $sut_title;
    $row['sut_lang'] = $sut_lang;
    $row['sut_form'] = $sut_form;
    $row['sut_fields_temps'] = serialize($sut_fields_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT sut_title FROM sb_site_users_temps WHERE sut_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_site_users_temps SET ?a WHERE sut_id=?d', $row, $_GET['id'], sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_EDIT_OK, $old_title));

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_SITE_USERS_FILTER_TEMP_EDIT_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_ERR_EDIT, $old_title), SB_MSG_WARNING);

                fSite_Users_Filter_Temp_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['sut_id'] = intval($_GET['id']);
            $html_str = fSite_Users_Filter_Temp_Get($row);

            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fSite_Users_Filter_Temp_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_SITE_USERS_FILTER_TEMP_EDIT_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_ERR_EDIT, $sut_title), SB_MSG_WARNING);

            fSite_Users_Filter_Temp_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_site_users_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sb_add_system_message(sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_ADD_OK, $sut_title));
                echo '<script>
                        sbReturnValue('.$id.');
                    </script>';
                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_site_users_temps WHERE sut_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_ADD_ERROR, $sut_title), false, 'warning');
            sb_add_system_message(sprintf(PL_SITE_USERS_FILTER_FORM_SUBMIT_ADD_SYS_ERROR, $sut_title), SB_MSG_WARNING);

            fSite_Users_Filter_Temp_Edit();
            return;
        }
    }
}

function fSite_Users_Filter_Temp_Delete()
{
    $id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_filter" AND pages.p_id=elems.e_p_id LIMIT 1');
    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_site_users_filter" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_SITE_USERS_DESIGN_DELETE_ERROR;
    }
}
?>