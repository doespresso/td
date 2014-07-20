<?php require_once(dirname(__FILE__).'/jheader.inc.php'); ?>
var sbFilesMenu = null;
var sbCurFile = null;

function sbFoldersClick()
{
    sbFoldersTree.active=false;
    try
    {
        sbFilesShow(sb_cms_empty_file + "?event=pl_filelist_read&path="+sbFoldersTree.getSelectedItemId() + "&files_exts="+sb_files_extensions + "&files_selected_file=" + sb_files_selected_file + "&files_link_title=" + sb_files_link_title);
    }
    catch(e)
    {
        sbFoldersTree.active=true;
    }
}

function sbFilesShow(url)
{
    sbSelectedFiles.splice(0, sbSelectedFiles.length);
    sbLoadAsync(url, sbFilesAfterShow);
    var files_list = sbGetE('files_list');
    files_list.innerHTML = '';
    sbShowMsgDiv("<?php echo TREE_LOADING; ?>", "loading.gif", false);
}

function sbFilesAfterShow(str)
{
    sbHideMsgDiv();
    sbFoldersTree.active=true;

    var files_list = sbGetE('files_list');
    var folders_list = sbGetE('folders_list');

    if (files_list)
    {
        files_list.innerHTML = str;
        var els = files_list.getElementsByTagName("DIV");
        if (sb_files_selected_file != "")
        {
            for (var i = 0; i < els.length; i++)
            {
                var file_name = els[i].getAttribute("el_id");
                if (file_name == sb_files_selected_file)
                {
                    sbSelectedFiles.push(file_name);
                    sbSelFile(els[i], true);
                    if (els[i].offsetTop > files_list.offsetHeight)
                        files_list.scrollTop = els[i].offsetTop;
                    break;
                }
            }
        }
        sb_files_selected_file = "";

        if ((sbCopiedFiles.length != 0 || sbCuttedFiles.length != 0) && sbCopyCutFolder == sbFoldersTree.getSelectedItemId())
        {
            for (var j = 0; j < els.length; j++)
            {
                var file_name = els[j].getAttribute("el_id");
                if (!file_name) continue;

                for (var i = 0; i < sbCopiedFiles.length; i++)
                {
                    if (file_name == sbCopiedFiles[i].el_id)
                    {
                        els[j].style.backgroundColor = "#E1FFE1";
                    }
                }
                for (var i = 0; i < sbCuttedFiles.length; i++)
                {
                    if (file_name == sbCuttedFiles[i].el_id)
                    {
                        els[j].style.backgroundColor = "#FFE1E1";
                    }
                }
            }
        }
    }
    else
    {
        alert('Fatal Error: The page has been loaded with errors');
    }
}

function sbFilesLoad()
{
    if (!_isOpera) document.body.focus();

    if (sbFilesMenu)
        sbFilesMenu.init();
}

function sbResizeFldiv()
{
    var files_list   = sbGetE("files_list");
    var folders_list = sbGetE("sb_folders_list");
    var files_table  = sbGetE("files_list_table");
    var files_header = sbGetE("files_list_header");
    var files_footer = sbGetE("files_list_footer");
    var con_el = sbGetE("event_content");

    files_list.style.width = 0;
    files_list.style.width = Math.max(con_el.offsetWidth - panel_width - 5, 0);

    if (_isIE && _browserVersion < 10)
    {
        files_table.style.height = Math.max(parseInt(document.body.clientHeight) - 25 - (files_header?files_header.offsetHeight:0) - (files_footer?files_footer.offsetHeight:0), 0);
        if (sbFoldersTree)
        {
            sbFoldersTree.allTree.style.height = files_table.offsetHeight - 1;
            if(folders_list.style.width != "")
                sbFoldersTree.allTree.style.width = parseInt(folders_list.style.width) - (_isIE ? 2 : 0);
        }
    }
    else
    {
        files_list.style.height = Math.max(parseInt(document.body.clientHeight) - 25 - (files_header?files_header.offsetHeight:0) - (files_footer?files_footer.offsetHeight:0), 0);
        folders_list.style.height = files_list.style.height;
        if (sbFoldersTree) sbFoldersTree.allTree.style.height = files_list.offsetHeight - 10;
    }
}

function sbClickFile(e)
{
    var el = sbEventTarget(e)

    while (el.parentNode && !el.getAttribute("el_id"))
        el = el.parentNode;

    var el_id = el.getAttribute("el_id");
    if (e && e.ctrlKey)
    {
        for (var i = 0; i < sbSelectedFiles.length; i++)
        {
            if (sbSelectedFiles[i] == el_id)
            {
                sbSelectedFiles.splice(i, 1);
                sbSelFile(el, false);
                return;
            }
        }
        sbSelectedFiles.push(el_id);
        sbSelFile(el, true);
    }
    else
    {
        for (var i = 0; i < sbSelectedFiles.length; i++)
        {
            sbSelFile(sbGetE("el_"+sbSelectedFiles[i]), false);
        }
        sbSelectedFiles.splice(0, sbSelectedFiles.length);
        sbSelectedFiles.push(el_id);
        sbSelFile(el, true);
    }

    if (typeof(sbFilesOnClick) == "function")
        sbFilesOnClick(el);
}


function sbSelFile(el, mode)
{
    if (!el) return;
    if (mode)
    {
        el.style.borderColor = "red";
        el.style.backgroundColor = "#EFE5D0";
    }
    else
    {
        el.style.borderColor = "#e6e6e6";
        var file_name = el.getAttribute("el_id");
        if (file_name)
        {
            for (var i = 0; i < sbCopiedFiles.length; i++)
            {
                if (file_name == sbCopiedFiles[i].el_id)
                {
                    el.style.backgroundColor = "#E1FFE1";
                    return;
                }
            }
            for (var i = 0; i < sbCuttedFiles.length; i++)
            {
                if (file_name == sbCuttedFiles[i].el_id)
                {
                    el.style.backgroundColor = "#FFE1E1";
                    return;
                }
            }
        }
        el.style.backgroundColor = "";
    }
}

function sbFilesShowMenu(e)
{
    sbSelFolder = 0;
    if (!sbFoldersTree.lastSelected)
        return;
    if (!sbFilesMenu)
        return;

    sbCurFile = sbEventTarget(e)
    while (sbCurFile.parentNode && !sbCurFile.getAttribute("el_id"))
        sbCurFile = sbCurFile.parentNode;

    var additionalShowed = false;

    if (sbCurFile.parentNode)
    {
        var found = false;
        var el_id = sbCurFile.getAttribute("el_id");
        for (i = 0; i < sbSelectedFiles.length; i++)
        {
            if (sbSelectedFiles[i] == el_id)
            {
                found = true;
                break;
            }
        }

        if (!found)
        {
            for (i = 0; i < sbSelectedFiles.length; i++)
                sbSelFile(sbGetE("el_"+sbSelectedFiles[i]), false);

            sbSelectedFiles.splice(0, sbSelectedFiles.length);
            sbSelectedFiles.push(el_id);
            sbSelFile(sbCurFile, true);
        }

        sbFilesMenu.showItem("cut");
        sbFilesMenu.showItem("copy");
        sbFilesMenu.showItem("rename");
        sbFilesMenu.showItem("delete");

        for (var i=0; i < sb_files_add_menu.length; i++)
        {
            sbFilesMenu.showItem("additional_"+i);
            additionalShowed = true;
        }
    }
    else
    {
        sbFilesMenu.hideItem("copy");
        sbFilesMenu.hideItem("cut");
        sbFilesMenu.hideItem("rename");
        sbFilesMenu.hideItem("delete");

        for (var i=0;i < sb_files_add_menu.length;i++)
        {
            if (sb_files_add_menu[i]['hide'])
            {
                sbFilesMenu.hideItem("additional_"+i);
            }
            else
            {
                additionalShowed = true;
                sbFilesMenu.showItem("additional_"+i);
            }
        }
    }

    if ((sbFoldersTree.getSelectedItemId() == sbCopyCutFolder && (sbCuttedFiles.length != 0 || sbCopiedFiles.length != 0)) || (sbCuttedFiles.length == 0 && sbCopiedFiles.length == 0))
    {
        sbFilesMenu.hideItem("paste");
    }
    else
    {
        sbFilesMenu.showItem("paste");
    }

    var pasteShowed = sbFilesMenu.isShowedItem("paste");
    var cutcopyShowed = (sbFilesMenu.isShowedItem("cut") || sbFilesMenu.isShowedItem("copy") || sbFilesMenu.isShowedItem("select_all"));
    var mainShowed = (sbFilesMenu.isShowedItem("rename") || sbFilesMenu.isShowedItem("delete") || sbFilesMenu.isShowedItem("upload") || sbFilesMenu.isShowedItem("refresh"));
    var sortShowed = (sbFilesMenu.isShowedItem("sort_time") || sbFilesMenu.isShowedItem("sort_size") || sbFilesMenu.isShowedItem("sort_name"));

    if (pasteShowed && (cutcopyShowed || mainShowed || additionalShowed || sortShowed))
        sbFilesMenu.showItem("paste_delimeter");
    else
        sbFilesMenu.hideItem("paste_delimeter");

    if (cutcopyShowed && (mainShowed || additionalShowed || sortShowed))
        sbFilesMenu.showItem("cutcopy_delimeter");
    else
        sbFilesMenu.hideItem("cutcopy_delimeter");

    if (mainShowed && (additionalShowed || sortShowed))
        sbFilesMenu.showItem("main_delimeter");
    else
        sbFilesMenu.hideItem("main_delimeter");

    if (additionalShowed && sortShowed)
        sbFilesMenu.showItem("additional_delimeter");
    else
        sbFilesMenu.hideItem("additional_delimeter");

    sbFilesMenu.show(e);
}

function sbClearFiles()
{
    for (i = 0; i < sbCuttedFiles.length; i++)
    {
        var el = sbGetE("el_" + sbCuttedFiles[i].el_id);
        if (el) el.style.backgroundColor = '';
    }
    for (i = 0; i < sbCopiedFiles.length; i++)
    {
        el = sbGetE("el_" + sbCopiedFiles[i].el_id);
        if (el) el.style.backgroundColor = '';
    }
    sbCopiedFiles.splice(0, sbCopiedFiles.length);
    sbCuttedFiles.splice(0, sbCuttedFiles.length);
    sbCopyCutFolder = 0;
}

function sbCutFiles()
{
    sbClearFiles();
    for (i = 0; i < sbSelectedFiles.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedFiles[i]);
        if (el)
        {
            el.style.backgroundColor = '#FFE1E1';
            var tmp = new Object();
            tmp.el_id = el.getAttribute("el_id");
            sbCuttedFiles.push(tmp);
        }
    }
    sbCopyCutFolder = sbFoldersTree.getSelectedItemId();
}

function sbCopyFiles()
{
    sbClearFiles();
    for (i = 0; i < sbSelectedFiles.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedFiles[i]);
        if (el)
        {
            el.style.backgroundColor = '#E1FFE1';
            var tmp = new Object();
            tmp.el_id = el.getAttribute("el_id");
            sbCopiedFiles.push(tmp);
        }
    }
    sbCopyCutFolder = sbFoldersTree.getSelectedItemId();
}

function sbSelectAll()
{
    var files_list_div = sbGetE("files_list_div");
    if (!files_list_div) return;
    var els = files_list_div.getElementsByTagName("DIV");
    sbSelectedFiles.splice(0, sbSelectedFiles.length);
    for (i = 0; i < els.length; i++)
    {
        var el_id = els[i].getAttribute("el_id");
        if (el_id)
        {
            sbSelectedFiles.push(el_id);
            els[i].style.borderColor = "red";
        }
    }
}

function sbProcessKey(e)
{
    sbSelFolder = 0;
    sbHideCMenu();

    if (!e) e = window.event;
    var el = sbEventTarget(e)
    if (el.tagName == "INPUT" || el.tagName == "TEXTAREA" || el.tagName == "SELECT" || el.tagName == "BUTTON")
        return;

    var files_list = sbGetE("files_list");
    if (!sbFoldersTree || !sbFoldersTree.lastSelected) return;

    switch (e.keyCode)
    {
        case 46:
            sbDeleteFiles();
            return sbCancelEvent(e);
	    case 65:
	        if (e.ctrlKey && sb_files_selectall_menu)
	            sbSelectAll();
	        return sbCancelEvent(e);
	    case 67:
	        if (e.ctrlKey && sb_files_copy_menu)
	            sbCopyFiles();
	        return sbCancelEvent(e);
        case 88:
	        if (e.ctrlKey && sb_files_cut_menu)
	            sbCutFiles();
	        return sbCancelEvent(e);
	    case 86:
	        if (e.ctrlKey && sb_files_paste_menu)
	        {
	            if (sbFoldersTree.getSelectedItemId() == sbCopyCutFolder && sbCuttedFiles.length != 0)
	               break;
	            sbFoldersPasteFiles();
	        }
	        return sbCancelEvent(e);
	    case 40:
	    case 38:
	       return sbCancelEvent(e);
    }
}

function sbRefreshFiles()
{
    sbFoldersClick();
}

function sbRenameFile()
{
    if (!sbCurFile)
        return;

    sbCurFile.style.borderColor = "green";

    var el_id = sbCurFile.getAttribute("el_id");

    var strPage = sb_cms_modal_dialog_file+"?event=pl_folders_rename_file&path="+encodeURI(el_id);
    var strAttr = "resizable=1,width=600,height=400";
    sbShowModalDialog(strPage, strAttr, sbFilesAfterRename);
}

function sbFilesAfterRename()
{
    if (!sbCurFile)
        return;

    if (!sbModalDialog.returnValue)
    {
        sbCurFile.style.borderColor = "red";
        return;
    }

    var el_id = sbCurFile.getAttribute("el_id");
    var el_title = sbGetE("el_title_"+el_id);
    var el_icon = sbGetE("el_icon_"+el_id);

    if (el_id && el_title)
    {
        el_title.innerHTML = '<a href="'+sbModalDialog.returnValue.id+'" target="_blank"><b>'+sbModalDialog.returnValue.title+'</b></a><br />';
        el_title.id = "el_title_"+sbModalDialog.returnValue.id;
        el_icon.innerHTML = sbModalDialog.returnValue.icon;
        el_icon.id = "el_icon_"+sbModalDialog.returnValue.id;

        sbCurFile.id = "el_"+sbModalDialog.returnValue.id;
        sbCurFile.setAttribute("el_id", sbModalDialog.returnValue.id);
    }

    sbCurFile.style.borderColor = "red";
}

function sbDeleteFiles()
{
    if (sbSelectedFiles.length == 0)
        return;

    if (!confirm("<?php echo FILES_CONFIRM_DELETE_MSG; ?>"))
        return;

    sbShowMsgDiv("<?php echo FILES_DELETING_MSG; ?>", "loading.gif", false);
    setTimeout(sbDeleteFilesHelper, 0);
    sbFoldersTree.active=false;
}

function sbDeleteFilesHelper()
{
    if (sbSelectedFiles.length == 0)
    {
        sbFoldersTree.active=true;
        sbHideMsgDiv();
        return;
    }

    try
    {
        while(sbSelectedFiles.length != 0)
        {
            var el = sbGetE("el_" + sbSelectedFiles.pop());
            if (!el)
                continue;
            var el_id = el.getAttribute("el_id");
            if (!el_id)
                continue;

            var res = sbLoadSync(sb_cms_empty_file + "?event=pl_folders_delete_files&path="+encodeURI(el_id));
            if (res != "TRUE")
            {
                alert("<?php echo FILES_DELETE_ERROR1; ?>"+el_id+"<?php echo FILES_DELETE_ERROR2; ?>");
                continue;
            }

            for (var i = 0; i < sbCuttedFiles.length; i++)
            {
                if (sbCuttedFiles[i].el_id == el_id)
                {
                    sbCuttedFiles.splice(i, 1);
                    i--;
                }
            }
            for (var i = 0; i < sbCopiedFiles.length; i++)
            {
                if (sbCopiedFiles[i].el_id == el_id)
                {
                    sbCopiedFiles.splice(i, 1);
                    i--;
                }
            }
        }
    }
    catch (e)
    {
        sbFoldersTree.active=true;
    }

    sbFoldersTree.active=true;

    var div_el = sbGetE("files_list_div");
    var from = "";
    if (div_el)
    {
        from = div_el.getAttribute("from");
    }
    sbFilesShow(sb_cms_empty_file + "?event=pl_filelist_read&path="+sbFoldersTree.getSelectedItemId()+"&files_from="+from+"&files_exts="+sb_files_extensions);
    sbHideMsgDiv();
}

function sbFilesSortBy(flag)
{
    sbFilesShow(sb_cms_empty_file + "?event=pl_filelist_read&path="+sbFoldersTree.getSelectedItemId()+"&sort="+flag+"&files_exts="+sb_files_extensions);
}

var sbImageEl = null;
function sbFilesShowImage(e, img)
{
    var el = sbEventTarget(e)
    var el_info = sbGetE("sb_files_show_image_div");
    var el_img = sbGetE("sb_files_show_image_img");
    var tabs_con = sbGetE("files_list");

    if(el && el_info && el_img && sbImageEl != el)
    {
        el_info.style.left = sbGetAbsoluteLeft(el) - tabs_con.scrollLeft + 50;
        var t = sbGetAbsoluteTop(el) - 24 - tabs_con.scrollTop + 50;
        if (t < 0)
        {
            t = sbGetAbsoluteTop(el) - 24 - tabs_con.scrollTop;
        }

        if (t + el_info.offsetHeight > tabs_con.offsetHeight)
        {
            var t = Math.max(0, sbGetAbsoluteTop(el) - 24 - tabs_con.scrollTop - el_info.offsetHeight);
        }

        el_info.style.top = t;
        el_info.innerHTML = "<table width='100%' height='100%'><tr><td valign='middle' align='center'><img src='" + sb_cms_img_url + "/loading.gif' height='16' width='16' /></td></tr></table>";
        el_info.style.visibility = "visible";

        sbImageEl = el;
        el_img.src = img;

        setTimeout("sbFilesLoadImage()", 500);

        sbAddEvent(el, "mouseout", sbFilesHideImage);
    }
}

function sbFilesLoadImage()
{
    if (!sbImageEl)
        return;

    var img = sbGetE("sb_files_show_image_img");
    if (!img.complete)
    {
        setTimeout("sbFilesLoadImage()", 500);
        return;
    }

    var w = 200;
    var h = 200;
    if (img.width > img.height)
    {
        if (img.width > w)
        {
            h = 0;
        }
        else
        {
            w = img.width;
            h = img.height;
        }
    }

    if (img.height >= img.width)
    {
        if (img.height > h)
        {
            w = 0;
        }
        else
        {
            h = img.height;
            w = img.width;
        }
    }

    sbGetE("sb_files_show_image_div").innerHTML = "<table width='100%' height='100%'><tr><td valign='middle' align='center'><img src='" + img.src + "'" + (w != 0 ? " width='" + w + "'" : "") + (h != 0 ? " height='" + h + "'" : "") + " /></td></tr>" +
                                                  "<tr><td style='border-top: 1px solid #000;height: 30px;text-align:center;'>" + img.width + " &times; " + img.height + "</td></tr></table>";
}

function sbFilesHideImage (e)
{
    if (sbImageEl)
    {
        sbRemoveEvent(sbImageEl, "mouseout", sbFilesHideImage);
        var el_info = sbGetE("sb_files_show_image_div");

        if (el_info)
        {
            el_info.style.visibility = "hidden";
            el_info.style.left = -1000;
            el_info.style.top = -1000;
        }

        sbImageEl = null;
    }
}

sbResizeFuncs[sbResizeFuncs.length]=sbResizeFldiv;
sbAddEvent(window, 'load', sbFilesLoad);

document.onkeydown=sbProcessKey;
<?php require_once(dirname(__FILE__).'/jfooter.inc.php'); ?>