<?php require_once(dirname(__FILE__).'/jheader.inc.php'); ?>
var sbElemsMenu = null;
var sbSelEl = null;
var sbShowLoadingDiv = true;

function sbResizeEldiv()
{
    var e_list = sbGetE("elems_list");
    var c_list = sbGetE("categs_list");
    var e_list_table = sbGetE("elems_list_table");
    var e_header = sbGetE("elems_list_header");
    var e_footer = sbGetE("elems_list_footer");
    var con_el = sbGetE("event_content");

    e_list.style.width = 0;
    e_list.style.width = Math.max(con_el.offsetWidth - panel_width - 5, 0);

    if (_isIE && _browserVersion < 10)
    {
        e_list_table.style.height = Math.max(parseInt(document.body.clientHeight) - 25 - (e_header?e_header.offsetHeight:0) - (e_footer?e_footer.offsetHeight:0), 0);
        if (sbCatTree) 
        {
            sbCatTree.allTree.style.height = e_list_table.offsetHeight - 1;
            if(c_list.style.width != "")
                sbCatTree.allTree.style.width = parseInt(c_list.style.width) - (_isIE ? 2 : 0);
        }
        
    }
    else
    {
        e_list.style.height = Math.max(parseInt(document.body.clientHeight) - 25 - (e_header?e_header.offsetHeight:0) - (e_footer?e_footer.offsetHeight:0), 0);
        c_list.style.height = e_list.style.height;
        if (sbCatTree) sbCatTree.allTree.style.height = e_list.offsetHeight - 10;
    }
}

function sbCategClick(id)
{
    sbCatTree.active=false;
    try
    {
    	var c_list = sbGetE("categs_list");
        sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id="+id + "&sel_el_id=" + sb_elems_selected_id);
    }
    catch(e)
    {
        sbCatTree.active=true;
    }
}

function sbElemsShow(url)
{
    sbSelectedEls.splice(0, sbSelectedEls.length);
    sbLoadAsync(url, sbAfterShowElems);
    var elems_list = sbGetE('elems_list');
    elems_list.innerHTML = '';
    if (sbShowLoadingDiv)
    	sbShowMsgDiv("<?php echo TREE_LOADING; ?>", "loading.gif", false);
}

function sbElemsHighlight(e, h_id)
{
    for (var i = 0; i < sbSelectedEls.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedEls[i]);
        sbSelElem(el, false);
    }
    
    sbSelectedEls.splice(0, sbSelectedEls.length);
    
    var found = false;
    var els = elems_list.getElementsByTagName("DIV");
    
    for (var i = 0; i < els.length; i++)
    {
        var el_id = els[i].getAttribute("el_id");
        var is_link = els[i].getAttribute("is_link");
        if (is_link == 0 && el_id == h_id)
        {
            sbSelectedEls.push(els[i].getAttribute("true_id"));
            sbSelElem(els[i], true);
            if (els[i].offsetTop > elems_list.offsetHeight)
                elems_list.scrollTop = els[i].offsetTop;
            
            found = true;
            break;
        }
    }
       
    if (!found) 
    {
        with (window.location)    
            href = protocol + "//" + host + "/" + pathname + "?event=" + sb_elems_event + "&sb_sel_id=" + h_id;
    }
    return sbCancelEvent(e);
}

function sbAfterShowElems(str)
{
	if (sbShowLoadingDiv)
    	sbHideMsgDiv();
    
    sbShowLoadingDiv = true;
    sbCatTree.active=true;

    var elems_list = sbGetE('elems_list');
    if (elems_list)
    {
        elems_list.innerHTML = str;
        var els = elems_list.getElementsByTagName("DIV");
        if (sb_elems_selected_id > 0)
        {
            for (var i = 0; i < els.length; i++)
            {
                var el_id = els[i].getAttribute("el_id");
                var is_link = els[i].getAttribute("is_link");
                if (is_link == 0 && el_id == sb_elems_selected_id)
                {
                    sbSelectedEls.push(els[i].getAttribute("true_id"));
                    sbSelElem(els[i], true);
                    if (els[i].offsetTop > elems_list.offsetHeight)
                        elems_list.scrollTop = els[i].offsetTop;
                        
                    if (typeof(sb_elems_on_click_func) == "function")
                        sb_elems_on_click_func(els[i]);
                        
                    break;
                }
            }
        }
        sb_elems_selected_id = -1;

        if ((sbCopiedEls.length != 0 || sbCuttedEls.length != 0) && sbCopyCutCat == sbCatTree.getSelectedItemId())
        {
            for (var j = 0; j < els.length; j++)
            {
                var true_id = els[j].getAttribute("true_id");
                if (!true_id) continue;

                for (var i = 0; i < sbCopiedEls.length; i++)
                {
                    if (true_id == sbCopiedEls[i].true_id)
                    {
                        els[j].style.backgroundColor = "#E1FFE1";
                    }
                }
                for (var i = 0; i < sbCuttedEls.length; i++)
                {
                    if (true_id == sbCuttedEls[i].true_id)
                    {
                        els[j].style.backgroundColor = "#FFE1E1";
                    }
                }
            }
        }
        
        if (typeof(sb_elems_after_load_func) == "function")
            setTimeout("sb_elems_after_load_func();", 1);
    }
    else
    {
        alert('Fatal Error: The page has been loaded with errors');
    }
}

function sbSelElem(el, mode)
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
        var true_id = el.getAttribute("true_id");
        if (true_id)
        {
            for (var i = 0; i < sbCopiedEls.length; i++)
            {
                if (true_id == sbCopiedEls[i].true_id)
                {
                    el.style.backgroundColor = "#E1FFE1";
                    return;
                }
            }
            for (var i = 0; i < sbCuttedEls.length; i++)
            {
                if (true_id == sbCuttedEls[i].true_id)
                {
                    el.style.backgroundColor = "#FFE1E1";
                    return;
                }
            }
        }
        
        el.style.backgroundColor = "";
    }
}

function sbElemsLoad()
{
    if (sbElemsMenu)
        sbElemsMenu.init();
}

function sbElemsShowFilter()
{
    var filter = sbGetE("sb_filter");
    var filter_arr = sbGetE("filter_arrow");
    if (!filter) return;
    if (filter.style.display == "none")
    {
        filter.style.display = "";
        filter_arr.src = sb_cms_img_url + "/fil_arrow_up.gif";
    }
    else
    {
        filter.style.display = "none";
        filter_arr.src = sb_cms_img_url + "/fil_arrow_down.gif";
    }
    sbResizeEldiv();
}

function sbElemsSetFilter()
{
    var frm = sbGetE("filter_form");
    var str = '';
    for (var i = 0; i < frm.elements.length; i++)
    {
        var el = frm.elements[i];
        if (el.type == "button") continue;
        if (el.type == "select-multiple")
        {
            var found = false;
            for (var j=0; j < el.options.length;j++)
            {
                if (el.options[j].selected)
                {
                    str += '&'+el.name+'[]=' + el.options[j].value;
                    found = true;
                }
            }
        }
        else if (el.type == "select-one")
        {
            if (el.value != -1)
                str += '&'+el.name+'='+el.value;
        }
        else if (el.value != '')
        {
            str += '&'+el.name+'='+el.value;
        }
    }

    if (str == '')
    {
        sbGetE("sb_filter_on").style.visibility = "hidden";
    
        if (sbCatTree.lastSelected)
            sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id="+sbCatTree.getSelectedItemId() + "&nofilter=1");
        return;
    }
    sbGetE("sb_filter_on").style.visibility = "visible";
    
    if (sbCatTree.lastSelected)
        sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id="+sbCatTree.getSelectedItemId() + str);
}

function sbElemsRemoveFilter(e)
{
    var frm = sbGetE("filter_form");
    for (var i = 0; i < frm.elements.length; i++)
    {
        var el = frm.elements[i];
        if (el.type == "text")
            el.value = '';
        else if(el.type == "checkbox")
            el.checked = false;
        else if(el.type == "select-one")
            el.options[0].selected = true;
        else if (el.type == "select-multiple")
        {
            for (var j = 0; j < el.options.length; j++)
                el.options[j].selected = false;
        }
    }
    sbGetE("sb_filter_on").style.visibility = "hidden";

    sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id="+sbCatTree.getSelectedItemId()+"&nofilter=1");
    if (e) 
        return sbCancelEvent(e);
}

function sbElemsClick(e)
{
	var e_list = sbGetE("elems_list");
    var el = sbEventTarget(e);

    while (el.parentNode && !el.getAttribute("true_id"))
        el = el.parentNode;

    var true_id = el.getAttribute("true_id");
    if (e && e.ctrlKey)
    {
        for (var i = 0; i < sbSelectedEls.length; i++)
        {
            if (sbSelectedEls[i] == true_id)
            {
                sbSelectedEls.splice(i, 1);
                sbSelElem(el, false);
                return;
            }
        }
        sbSelectedEls.push(true_id);
        sbSelElem(el, true);
    }
    else
    {
        for (var i = 0; i < sbSelectedEls.length; i++)
        {
            sbSelElem(sbGetE("el_"+sbSelectedEls[i]), false);
        }
        sbSelectedEls.splice(0, sbSelectedEls.length);
        sbSelectedEls.push(true_id);
        sbSelElem(el, true);
    }

    if (typeof(sb_elems_on_click_func) == "function")
        sb_elems_on_click_func(el);
}

function sbElemsProcessKey(e)
{
    sbSelCat = 0;
    sbHideCMenu();
    
    if (!e) e = window.event;
    var el = sbEventTarget(e)
    if (el.tagName == "INPUT" || el.tagName == "TEXTAREA" || el.tagName == "SELECT" || el.tagName == "BUTTON")
        return;
    
    var elems_list = sbGetE("elems_list_div");
    if (!sbCatTree || !sbCatTree.lastSelected || !elems_list) return;

    var catLevel = sbCatTree.lastSelected.level;
    switch (e.keyCode)
    {
        case 46:
        	if (catLevel > sb_elems_delete_menu_level)
            	sbElemsDelete();
            return sbCancelEvent(e);
        case 13:
            if (sbSelectedEls.length == 1)
		        sbElemsEdit(sbGetE("el_"+sbSelectedEls[0]));
		    return sbCancelEvent(e);
	    case 65:
	        if (e.ctrlKey && sb_elems_selectall_menu)
	            sbElemsSelectAll();
	        return sbCancelEvent(e);
	    case 67:
	        if (e.ctrlKey && sb_elems_copy_menu && catLevel >= sb_elems_copy_menu_level)
	            sbElemsCopy();
	        return sbCancelEvent(e);
        case 88:
	        if (e.ctrlKey && sb_elems_cut_menu && catLevel >= sb_elems_cut_menu_level)
	            sbElemsCut();
	        return sbCancelEvent(e);
	    case 86:
	        if (e.ctrlKey && (sb_categs_paste_menu != "none" || sb_categs_pastelinks_menu) && catLevel >= sb_elems_paste_menu_level)
	        {
	            if (sbCatTree.getSelectedItemId() == sbCopyCutCat && sbCuttedEls.length != 0)
	               break;
	             
	            if (sb_categs_paste_menu == "none")
	            {  
	                sbElemsPasteLinks();
	            }
	            else
	            {
	                if (sb_categs_paste_menu == "cut" && sbCuttedEls.length != 0 || sb_categs_paste_menu == "copy" && sbCopiedEls.length != 0 || sb_categs_paste_menu == "all")
	                    sbElemsPaste();
	                else
	                    sbElemsPasteLinks();
	            }
	        }
	        return sbCancelEvent(e);
	    case 40:
	    	if (e.ctrlKey)
                return;
            
            var nextEl = null;    
            if (sbSelectedEls.length >= 1)
            {
                for (var i = 0; i < sbSelectedEls.length; i++)
		        {
		            sbSelElem(sbGetE("el_"+sbSelectedEls[i]), false);
		        }
		        
		        nextEl = sbGetE("el_"+sbSelectedEls[i - 1]).nextSibling;
		        if (nextEl && !nextEl.getAttribute("true_id"))
		        {
		        	nextEl = null;
		        }
		        	
                sbSelectedEls.splice(0, sbSelectedEls.length);
            }
            else
            {
            	var oDivs = elems_list.getElementsByTagName('DIV');
            	if (oDivs)
            	{
	            	for (var i = 0; i < oDivs.length; i++)
	                {
	                    if (oDivs[i].getAttribute('true_id'))
	                    {
	                    	sbSelectedEls.push(oDivs[i].getAttribute('true_id'));
	                    	sbSelElem(oDivs[i], true);
	                    	if (oDivs[i].offsetTop < sbGetE("elems_list").scrollTop)
	                        {
	                        	oDivs[i].scrollIntoView();
	                        }
	                        return;
	                    }
	                }
	            }
            }
            
            if (!nextEl)
            {
            	var elTable = document.getElementById('elems_nav_table_top');
            	if (elTable.style.display != "none")
            	{
            		var catId = sbCatTree.getSelectedItemId();
	            	var div_el = sbGetE("elems_list_div");
			        var from = "";
			        
			        if (div_el)
			        {
			           from = parseInt(div_el.getAttribute("from")) + 1;
			        }
			        var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + catId + "&page_elems=" + from;
			        setTimeout("sbElemsShow('"+url+"')", 0);
	            }
            }
            
            if (nextEl)
            {
            	sbSelectedEls.push(nextEl.getAttribute('true_id'));
                sbSelElem(nextEl, true);
                if (nextEl.offsetTop > sbGetE("elems_list").offsetHeight + sbGetE("elems_list").scrollTop)
                {
                	nextEl.scrollIntoView();
                }
            }
            return sbCancelEvent(e);    
	    case 38:
	    	if (e.ctrlKey)
                return;
            
            var prevEl = null;    
            if (sbSelectedEls.length >= 1)
            {
		        prevEl = sbGetE("el_"+sbSelectedEls[sbSelectedEls.length - 1]).previousSibling;
		        if (prevEl && !prevEl.getAttribute("true_id"))
		        {
		        	prevEl = null;
		        }
		        else if (prevEl)
		        {	
		            for (var i = 0; i < sbSelectedEls.length; i++)
		        	{
		            	sbSelElem(sbGetE("el_"+sbSelectedEls[i]), false);
		        	}
		        
                	sbSelectedEls.splice(0, sbSelectedEls.length);
                }
            }
            else
            {
            	var oDivs = elems_list.getElementsByTagName('DIV');
            	if (oDivs)
            	{
	            	for (var i = oDivs.length - 1; i >= 0 ; i--)
	                {
	                    if (oDivs[i].getAttribute('true_id'))
	                    {
	                    	sbSelectedEls.push(oDivs[i].getAttribute('true_id'));
	                    	sbSelElem(oDivs[i], true);
	                    	if (oDivs[i].offsetTop > sbGetE("elems_list").offsetHeight + sbGetE("elems_list").scrollTop)
	                        {
	                        	oDivs[i].scrollIntoView();
	                        }
	                        return;
	                    }
	                }
	            }
            }
            
            if (!prevEl)
            {
            	var elTable = document.getElementById('elems_nav_table_top');
            	if (elTable.style.display != "none")
            	{
            		var catId = sbCatTree.getSelectedItemId();
	            	var div_el = sbGetE("elems_list_div");
			        var from = "";
			        
			        if (div_el)
			        {
			           from = parseInt(div_el.getAttribute("from")) - 1;
			        }
			        
			        if (from <= 0)
			        	return;
			        	
			        var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + catId + "&page_elems=" + from;
			        setTimeout("sbElemsShow('"+url+"')", 0);
	            }
            }
            
            if (prevEl)
            {
            	sbSelectedEls.push(prevEl.getAttribute('true_id'));
                sbSelElem(prevEl, true);
                if (prevEl.offsetTop < sbGetE("elems_list").scrollTop)
                {
                	prevEl.scrollIntoView();
                }
            }
	        return sbCancelEvent(e);
    }
}

function sbElemsShowMenu(e)
{       
    sbSelCat = 0;
    if (!sbCatTree.lastSelected)
        return;

    if (!sbElemsMenu)
        return;

    var show_menu = sbGetE("elems_show_menu");
    if (!show_menu)
        return;

    if (show_menu.innerHTML == "0")
        return;

    var level = sbCatTree.lastSelected.level;
    if (level < sb_elems_menu_level)
        return;

    sbSelEl = sbEventTarget(e)
    while (sbSelEl.parentNode && !sbSelEl.getAttribute("true_id"))
        sbSelEl = sbSelEl.parentNode;

    var additionalShowed = false;
    var sortingShowed = false;

    if (sbSelEl.parentNode)
    {
        var found = false;
        var true_id = sbSelEl.getAttribute("true_id");
        for (var i = 0; i < sbSelectedEls.length; i++)
        {
            if (sbSelectedEls[i] == true_id)
            {
                found = true;
                break;
            }
        }
        if (!found)
        {
            for (i = 0; i < sbSelectedEls.length; i++)
                sbSelElem(sbGetE("el_"+sbSelectedEls[i]), false);

            sbSelectedEls.splice(0, sbSelectedEls.length);
            sbSelectedEls.push(true_id);
            sbSelElem(sbSelEl, true);
        }

        if (level < sb_elems_cut_menu_level)
            sbElemsMenu.hideItem("cut");
        else
            sbElemsMenu.showItem("cut");

        if (level < sb_elems_copy_menu_level)
            sbElemsMenu.hideItem("copy");
        else
            sbElemsMenu.showItem("copy");

        if (level < sb_elems_edit_menu_level)
            sbElemsMenu.hideItem("edit");
        else
            sbElemsMenu.showItem("edit");

        if (level < sb_elems_add_menu_level)
            sbElemsMenu.hideItem("add");
        else
            sbElemsMenu.showItem("add");

        if (level < sb_elems_delete_menu_level)
            sbElemsMenu.hideItem("delete");
        else
            sbElemsMenu.showItem("delete");

        for (var i=0; i < sb_elems_add_menu.length; i++)
        {
            if (level < sb_elems_add_menu[i]['level'])
            {
                sbElemsMenu.hideItem("additional_"+i);
            }
            else
            {
                additionalShowed = true;
                sbElemsMenu.showItem("additional_"+i);
            }
        }
    }
    else
    {
        sbElemsMenu.hideItem("copy");
        sbElemsMenu.hideItem("cut");
        sbElemsMenu.hideItem("edit");
        sbElemsMenu.hideItem("delete");

        if (level < sb_elems_add_menu_level)
            sbElemsMenu.hideItem("add");
        else
            sbElemsMenu.showItem("add");

        for (var i=0;i < sb_elems_add_menu.length;i++)
        {
            if (sb_elems_add_menu[i]['hide'])
            {
                sbElemsMenu.hideItem("additional_"+i);
            }
            else
            {
                if (level < sb_elems_add_menu[i]['level'])
                {
                    sbElemsMenu.hideItem("additional_"+i);
                }
                else
                {
                    additionalShowed = true;
                    sbElemsMenu.showItem("additional_"+i);
                }
            }
        }
    }
    if (level < sb_elems_paste_menu_level || (sbCatTree.getSelectedItemId() == sbCopyCutCat && sbCuttedEls.length != 0) || (sbCuttedEls.length == 0 && sbCopiedEls.length == 0))
    {
        sbElemsMenu.hideItem("paste");
        sbElemsMenu.hideItem("paste_links");
    }
    else
    {
        if ((sb_categs_paste_menu == "copy" && sbCopiedEls.length == 0) || (sb_categs_paste_menu == "cut" && sbCuttedEls.length == 0))
            sbElemsMenu.hideItem("paste");
        else
            sbElemsMenu.showItem("paste");
            
        if (sbCopiedEls.length != 0)
            sbElemsMenu.showItem("paste_links");
        else
        	sbElemsMenu.hideItem("paste_links");
    }

    var pasteShowed = (sbElemsMenu.isShowedItem("paste") || sbElemsMenu.isShowedItem("paste_links"));
    var cutcopyShowed = (sbElemsMenu.isShowedItem("cut") || sbElemsMenu.isShowedItem("copy") || sbElemsMenu.isShowedItem("select_all"));
    var addeditShowed = (sbElemsMenu.isShowedItem("add") || sbElemsMenu.isShowedItem("edit") || sbElemsMenu.isShowedItem("delete") || sbElemsMenu.isShowedItem("refresh"));

    if (pasteShowed && (cutcopyShowed || addeditShowed || additionalShowed || sb_elems_sorting))
        sbElemsMenu.showItem("paste_delimeter");
    else
        sbElemsMenu.hideItem("paste_delimeter");

    if (cutcopyShowed && (addeditShowed || additionalShowed || sb_elems_sorting))
        sbElemsMenu.showItem("cutcopy_delimeter");
    else
        sbElemsMenu.hideItem("cutcopy_delimeter");

    if (addeditShowed && (additionalShowed || sb_elems_sorting))
        sbElemsMenu.showItem("addedit_delimeter");
    else
        sbElemsMenu.hideItem("addedit_delimeter");

	if (additionalShowed && sb_elems_sorting)
		sbElemsMenu.showItem("additional_delimeter");
	else
		sbElemsMenu.hideItem("additional_delimeter");

	sbElemsMenu.show(e);
}

function sbElemsClear ()
{
    for (i = 0; i < sbCuttedEls.length; i++)
    {
        var el = sbGetE("el_" + sbCuttedEls[i].true_id);
        if (el) el.style.backgroundColor = '';
    }
    for (i = 0; i < sbCopiedEls.length; i++)
    {
        el = sbGetE("el_" + sbCopiedEls[i].true_id);
        if (el) el.style.backgroundColor = '';
    }
    sbCopiedEls.splice(0, sbCopiedEls.length);
    sbCuttedEls.splice(0, sbCuttedEls.length);
    sbCopyCutCat = 0;
}

function sbElemsCut()
{
    sbElemsClear();
    for (var i = 0; i < sbSelectedEls.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedEls[i]);
        if (el)
        {
            el.style.backgroundColor = '#FFE1E1';
            var tmp = new Object();
            tmp.true_id = el.getAttribute("true_id");
            tmp.el_id = el.getAttribute("el_id");
            tmp.is_link = el.getAttribute("is_link");
            sbCuttedEls.push(tmp);
        }
    }
    sbCopyCutCat = sbCatTree.getSelectedItemId();
}

function sbElemsCopy()
{
    sbElemsClear();
    for (var i = 0; i < sbSelectedEls.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedEls[i]);
        if (el)
        {
            el.style.backgroundColor = '#E1FFE1';
            var tmp = new Object();
            tmp.true_id = el.getAttribute("true_id");
            tmp.el_id = el.getAttribute("el_id");
            tmp.is_link = el.getAttribute("is_link");
            sbCopiedEls.push(tmp);
        }
    }
    sbCopyCutCat = sbCatTree.getSelectedItemId();
}

function sbElemsSelectAll()
{
    var elems_list_div = sbGetE("elems_list_div");
    if (!elems_list_div) return;

    var els = elems_list_div.getElementsByTagName("div");
    sbSelectedEls.splice(0, sbSelectedEls.length);

    for (var i = 0; i < els.length; i++)
    {
        var true_id = els[i].getAttribute("true_id");
        if (true_id)
        {
            sbSelectedEls.push(true_id);
            els[i].style.borderColor = "red";
            els[i].style.backgroundColor = "#EFE5D0";
        }
    }
}

function sbElemsSortBy(field)
{
    sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id="+sbCatTree.getSelectedItemId() + "&sort_field="+field);
}

function sbElemsSetActive(field)
{
	var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;
    if (catLevel < sb_elems_edit_menu_level)
        return;
        
   	var ids = "0";
    for (var i = 0; i < sbSelectedEls.length; i++)
    {
        var el = sbGetE("el_" + sbSelectedEls[i]);
        if (el)
            ids += "," + el.getAttribute("el_id");
    }
    
    var res = sbLoadSync(sb_cms_empty_file + "?event=pl_categs_set_active&ids=" + ids + "&table=" + sb_elems_table + "&id_field=" + sb_elems_id_field + "&title_field=" + sb_elems_title_field + "&field=" + field + "&plugin_ident=" + sb_elems_plugin_ident + "&cat_id=" + catId);
    if (res != "TRUE")
    {
        alert("<?php echo ELEMS_SET_ACTIVE_ERROR; ?>");
        return;
    }

    var div_el = sbGetE("elems_list_div");
    var from = "";
    if (div_el)
    {
        from = div_el.getAttribute("from");
    }
    var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + sbCatTree.getSelectedItemId() + "&page_elems=" + from;
    sbElemsShow(url);
}

function sbElemsEdit(e)
{
    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;
    if (catLevel < sb_elems_edit_menu_level)
        return;

    if (e)
    {
        sbElemsClick(e);
        sbSelEl = sbGetE("el_" + sbSelectedEls[sbSelectedEls.length-1]);
    }
    
    if (!sbSelEl) return;
	
	var el_ids = "";
	for (var i = 0; i < sbSelectedEls.length; i++)
    {
    	var el = sbGetE("el_" + sbSelectedEls[i]);
    	if (el)
        {
        	el_ids += el.getAttribute("el_id") + ",";
			el.style.borderColor = "green";
			
		    if (sb_elems_before_edit_event != "")
		    {
		        var res = sbLoadSync(sb_cms_empty_file + "?event=" + sb_elems_before_edit_event + "&id=" + el.getAttribute("el_id") + "&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure);
		        if (res != "")
		        {
		            sbShowMsgDiv(res, 'warning.png');
		            sbSelEl.style.borderColor = "red";
		            if (e) 
		                return sbCancelEvent(e);
		                
		            return;
		        }
			}
		}
	}

    el_ids = el_ids.substr(0, el_ids.length - 1);

    var el_id = sbSelEl.getAttribute("el_id");
    var true_id = sbSelEl.getAttribute("true_id");
    var is_link = sbSelEl.getAttribute("is_link");

	strPage = sb_cms_modal_dialog_file+"?event="+sb_elems_edit_event+"&id=" + el_id + "&ids=" + el_ids + "&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure + "&plugin_ident=" + sb_elems_plugin_ident + "&link_id=" + true_id + "&link_src_cat_id=" + is_link;
	strAttr = "width="+sb_elems_edit_dlg_width+",height="+sb_elems_edit_dlg_height+",resizable=1";
	sbShowModalDialog(strPage, strAttr, sbElemsAfterEdit);

	if (e)
		return sbCancelEvent(e);
}

function sbElemsAfterEdit()
{
    if (!sbSelEl)
        return;

    if (!sbModalDialog.returnValue)
    {
        sbSelEl.style.borderColor = "red";
        return;
    }

    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;
    var el_id = sbSelEl.getAttribute("el_id");

    if (typeof(sbModalDialog.returnValue) != "object" && sbModalDialog.returnValue == "refresh")
    {
        var div_el = sbGetE("elems_list_div");
        var from = "";
        if (div_el)
        {
           from = div_el.getAttribute("from");
        }
        var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + catId + "&page_elems=" + from;
        setTimeout("sbElemsShow('"+url+"')", 0);
    }
    else if (sbModalDialog.returnValue.html != "" && sbModalDialog.returnValue.footer != "")
    {
        var elems_list_div = sbGetE("elems_list_div");
        if (elems_list_div)
        {
            var els = elems_list_div.getElementsByTagName("div");
            for (var i = 0; i < els.length; i++)
            {
                var tmp_id = els[i].getAttribute("el_id");
                var is_link = els[i].getAttribute("is_link");
                if (tmp_id && tmp_id == el_id)
                {
                    var tmp_t_id = els[i].getAttribute("true_id");
                    var el = sbGetE("el_td_"+tmp_t_id);
                    var ch_el = sbGetE("el_change_td_"+tmp_t_id);

                    el.innerHTML = sbModalDialog.returnValue.html;
                    if (is_link != 0 && sbModalDialog.returnValue.footer_link && sbModalDialog.returnValue.footer_link != "")
                        ch_el.innerHTML = sbModalDialog.returnValue.footer_link;
                    else
                        ch_el.innerHTML = sbModalDialog.returnValue.footer;
                }
            }
        }
    }
    else
    {
        alert("<?php echo ELEMS_EDIT_ERROR; ?>");
        sbSelEl.style.borderColor = "red";
        return;
	}

    if (sb_elems_after_edit_event != "")
    {
		for (var i = 0; i < sbSelectedEls.length; i++)
	    {
			var el = sbGetE("el_" + sbSelectedEls[i]);
	    	if (el)
	        {
		        var res = sbLoadSync(sb_cms_empty_file + "?event="+sb_elems_after_edit_event+"&id=" + el.getAttribute("el_id") + "&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure);
		        if (res != "")
		        {
		            alert(res);
		        }
			}
		}
    }

    if (typeof(sb_elems_after_edit_func) == "function" && (typeof(sbModalDialog.returnValue.not_call) == "undefined" || sbModalDialog.returnValue.not_call != 1))
        sb_elems_after_edit_func(el_id);
        
    sbSelEl.style.borderColor = "red";
}

function sbElemsAdd()
{
    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;

    if (catLevel < sb_elems_add_menu_level)
        return;

    if (sb_elems_before_add_event != "")
    {
        var res = sbLoadSync(sb_cms_empty_file + "?event="+sb_elems_before_add_event+"&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure);
        if (res != "")
        {
            sbShowMsgDiv(res, 'warning.png');
            return;
        }
    }

    strPage = sb_cms_modal_dialog_file+"?event="+sb_elems_add_event + "&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure + "&plugin_ident="+sb_elems_plugin_ident;
    strAttr = "width="+sb_elems_add_dlg_width+",height="+sb_elems_add_dlg_height+",resizable=1";
    sbShowModalDialog(strPage, strAttr, sbElemsAfterAdd);
}

function sbElemsAfterAdd()
{
    if (!sbModalDialog.returnValue)
        return;

    if (!sbModalDialog.returnValue)
    {
        alert("<?php echo ELEMS_ADD_ERROR; ?>");
        return;
    }

    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;
    var div_el = sbGetE("elems_list_div");
    var el_id = sbModalDialog.returnValue;
    
    var from = "";
    if (div_el)
    {
        from = div_el.getAttribute("from");
    }
    var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + catId + "&page_elems=" + from;
    setTimeout("sbElemsShow('"+url+"')", 1);

    if (sb_elems_after_add_event != "")
    {
        var res = sbLoadSync(sb_cms_empty_file + "?event="+sb_elems_after_add_event+"&id=" + el_id + "&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure);
        if (res != "")
        {
            alert(res);
        }
    }

    if (typeof(sb_elems_after_add_func) == "function"  && (typeof(sbModalDialog.returnValue.not_call) == "undefined" || sbModalDialog.returnValue.not_call != 1))
        sb_elems_after_add_func(el_id);
}

function sbElemsDelete(notShowMsg)
{
    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;

    if (typeof(sb_elems_before_delete_func) == "function")
    {
        if (!sb_elems_before_delete_func(catId))
            return;
    }
       
    if (catLevel < sb_elems_delete_menu_level || sbSelectedEls.length == 0)
        return;

    if (!notShowMsg)
        if (!confirm("<?php echo ELEMS_CONFIRM_DELETE; ?>"))
            return;

    sbShowMsgDiv("<?php echo ELEMS_DELETING_MSG; ?>", "loading.gif", false);
    setTimeout(sbElemsDeleteHelper, 0);
    sbCatTree.active=false;
}

function sbElemsDeleteHelper()
{
    var catId = sbCatTree.getSelectedItemId();
    var catLevel = sbCatTree.lastSelected.level;

    if (catLevel < sb_elems_delete_menu_level || sbSelectedEls.length == 0)
    {
        sbCatTree.active=true;
        sbHideMsgDiv();
        return;
    }

    var sOutReq = sb_cms_empty_file + "?event="+sb_elems_delete_event+"&cat_id=" + catId + "&cat_level=" + catLevel + "&cat_closed=" + sbCatTree.lastSelected.secure;
    var sOwnReq = sb_cms_empty_file + "?event=pl_categs_delete_elems&table="+sb_elems_table+"&id_field="+sb_elems_id_field+"&title_field="+sb_elems_title_field+"&cat_id=" + catId + "&plugin=" + sb_elems_plugin+"&plugin_ident="+sb_elems_plugin_ident;
    var num_error = 0;
    var num_all = sbSelectedEls.length;
    
    try
    {
        while(sbSelectedEls.length != 0)
        {
            var el = sbGetE("el_" + sbSelectedEls.pop());
            var el_id = el.getAttribute("el_id");
            var true_id = el.getAttribute("true_id");
            var is_link = el.getAttribute("is_link");

            if (sb_elems_delete_event != "")
            {
                var res = "";
                if (is_link == 0)
                {
                    res = sbLoadSync(sOutReq + "&id=" + el_id);
                }
                if (res != "")
                {
                    alert(res);
                    continue;
                }
            }

            var res = sbLoadSync(sOwnReq + "&true_id="+true_id + "&el_id=" + el_id + "&is_link=" + is_link);
            if (res == "FALSE")
            {
            	num_error++;
                alert("<?php echo ELEMS_DELETE_ERROR; ?>");
                continue;
            }
            
            for (var i = 0; i < sbCuttedEls.length; i++)
            {
                if (sbCuttedEls[i].true_id == true_id || (!is_link && sbCuttedEls[i].el_id == el_id))
                {
                    sbCuttedEls.splice(i, 1); 
                    i--;
                }
            }
            for (var i = 0; i < sbCopiedEls.length; i++)
            {
                if (sbCopiedEls[i].true_id == true_id || (!is_link && sbCopiedEls[i].el_id == el_id))
                {
                    sbCopiedEls.splice(i, 1);
                    i--;
                }
            }
        }
        
        if (typeof(sb_elems_after_delete_func) == "function" && num_error != num_all)
	    {
	        sb_elems_after_delete_func();
	    }
    }
    catch (e)
    {
        sbCatTree.active=true;
    }
    
    sbCatTree.active=true;
    
    var div_el = sbGetE("elems_list_div");
    var from = "";
    if (div_el)
    {
        from = div_el.getAttribute("from");
    }
    sbElemsShow(sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + catId + "&page_elems=" + from);
    sbHideMsgDiv();
}

function sbElemsRefresh()
{
    sbCategClick(sbCatTree.getSelectedItemId());
}

function sbShowCommentaryWindow(plIdent, elId, elTitle)
{
    sbShowDialog(sb_cms_dialog_file + "?event=pl_comments_show&pl_ident=" + plIdent + "&id=" + elId + "&title=" + sbEscapeEx(elTitle), "resizable=1,width=950,height=700");
}

sbResizeFuncs[sbResizeFuncs.length]=sbResizeEldiv;
sbAddEvent(window, 'load', sbElemsLoad);

document.onkeydown=sbElemsProcessKey;
<?php require_once(dirname(__FILE__).'/jfooter.inc.php'); ?>